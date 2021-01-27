<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Exception;
use Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse;
use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as IngenicoPayment;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Ingenico\Connect\Sdk\Domain\Refund\RefundResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\ConfigProvider;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\Status\Payment\ResolverInterface as PaymentResolverInterface;
use Ingenico\Connect\Model\Ingenico\Status\Refund\ResolverInterface as RefundResolverInterface;
use Ingenico\Connect\Model\StatusResponseManager;
use Ingenico\Connect\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__get
 */
class RetrievePayment extends AbstractAction implements ActionInterface
{
    /**
     * @var PaymentResolverInterface
     */
    private $paymentStatusResolver;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GetHostedCheckoutStatus
     */
    private $getHostedCheckoutStatus;

    /**
     * @var RefundResolverInterface
     */
    private $refundStatusResolver;

    /**
     * RetrievePayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param PaymentResolverInterface $paymentStatusResolver
     * @param RefundResolverInterface $refundStatusResolver
     * @param OrderRepositoryInterface $orderRepository
     * @param GetHostedCheckoutStatus $getHostedCheckoutStatus
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        PaymentResolverInterface $paymentStatusResolver,
        RefundResolverInterface $refundStatusResolver,
        OrderRepositoryInterface $orderRepository,
        GetHostedCheckoutStatus $getHostedCheckoutStatus
    ) {
        $this->paymentStatusResolver = $paymentStatusResolver;
        $this->refundStatusResolver = $refundStatusResolver;
        $this->orderRepository = $orderRepository;
        $this->getHostedCheckoutStatus = $getHostedCheckoutStatus;

        parent::__construct($statusResponseManager, $ingenicoClient, $transactionManager, $config);
    }

    /**
     * Will retrieve updates for all transactions/objects related to the order (payment, capture, refund)
     *
     * @param Order $order
     * @return bool
     * @throws LocalizedException
     */
    public function process(Order $order)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();

        $orderWasUpdated = false;

        if (ConfigProvider::CODE !== $payment->getMethod()) {
            throw new LocalizedException(__('This order was not placed via Ingenico ePayments'));
        }
        $orderTransactions = $this->transactionManager->retrieveTransactions($payment);

        $ingenicoPaymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        if (!$ingenicoPaymentId && empty($orderTransactions)) {
            try {
                return $this->updateHostedCheckoutStatus($order);
            } catch (Exception $e) {
                throw new LocalizedException(__('Order is not linked with Ingenico ePayments orders.'));
            }
        }

        foreach ($orderTransactions as $transaction) {
            $currentStatus = $this->getCurrentStatus($payment, $transaction);
            if (!$currentStatus) {
                continue;
            }

            $updateStatus = $this->getUpdateStatus($order, $currentStatus);
            if ($this->requiresUpdate($currentStatus, $updateStatus)) {
                if ($updateStatus instanceof RefundResponse) {
                    $creditMemo = $order->getCreditmemosCollection()->getFirstItem();
                    $this->refundStatusResolver->resolve($creditMemo, $updateStatus);
                } else {
                    $this->paymentStatusResolver->resolve($order, $updateStatus);
                }

                // @TODO: this looks like an ugly fix. Extend the integration test suite to check if this is required:
                // @TODO: this only needs to be tested for UndoCapturePaymentObserver
                /** @var Payment\Transaction $transaction */
                $this->addTransactionToOrder($order, $transaction);
                // @TODO: end ugly fix
                $order->addRelatedObject($payment);
                $orderWasUpdated = true;
            } else {
                $this->statusResponseManager->set($payment, $updateStatus->id, $updateStatus);
            }
        }

        if ($orderWasUpdated) {
            $this->orderRepository->save($order);
        }

        return $orderWasUpdated;
    }

    /**
     * @param Payment $payment
     * @param TransactionInterface $transaction
     * @return false|IngenicoPayment
     */
    private function getCurrentStatus($payment, $transaction)
    {
        $currentStatus = $this->statusResponseManager->get($payment, $transaction->getTxnId());

        return $currentStatus;
    }

    /**
     * @param Order $order
     * @param IngenicoPayment|RefundResult|Capture $currentStatus
     * @return CaptureResponse|PaymentResponse|RefundResponse
     * @throws LocalizedException
     */
    private function getUpdateStatus(Order $order, $currentStatus)
    {
        $merchant = $this->ingenicoClient
            ->getIngenicoClient($order->getStoreId())
            ->merchant($this->ePaymentsConfig->getMerchantId($order->getStoreId()));

        if ($currentStatus instanceof RefundResult) {
            $response = $merchant->refunds()->get($currentStatus->id);
        } elseif ($currentStatus instanceof IngenicoPayment) {
            $response = $merchant->payments()->get($currentStatus->id);
        } elseif ($currentStatus instanceof Capture) {
            $response = $merchant->captures()->get($currentStatus->id);
        } else {
            throw new LocalizedException(__('Can not pull update.'));
        }

        return $response;
    }

    /**
     * Try to query the hosted checkout
     *
     * @param Order|OrderInterface $order
     * @return bool
     * @throws Exception
     */
    private function updateHostedCheckoutStatus(Order $order)
    {
        $hostedCheckoutId = $order->getPayment()->getAdditionalInformation(Config::HOSTED_CHECKOUT_ID_KEY);
        $order = $this->getHostedCheckoutStatus->process($hostedCheckoutId);
        $ingenicoPaymentId = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        return $ingenicoPaymentId !== null;
    }

    /**
     * @param CaptureResponse|PaymentResponse|RefundResponse $currentStatus
     * @param CaptureResponse|PaymentResponse|RefundResponse $updateStatus
     * @return bool
     */
    private function requiresUpdate($currentStatus, $updateStatus): bool
    {
        return $currentStatus->status !== $updateStatus->status ||
            $currentStatus->statusOutput->toJson() !== $updateStatus->statusOutput->toJson();
    }

    /**
     * @fixme: this method can be most likely be removed entirely as soon
     * as the integration test also covers UndoCapturePaymentObserver
     * @param Order $order
     * @param Payment\Transaction $transaction
     * @return Payment\Transaction|null
     */
    private function addTransactionToOrder(Order $order, Payment\Transaction $transaction)
    {
        $transaction = $this->transactionManager->retrieveTransaction($transaction->getTxnId());
        if ($transaction !== null) {
            $found = false;
            foreach ($order->getRelatedObjects() as $relatedObject) {
                if ($relatedObject instanceof Payment\Transaction &&
                    (int) $relatedObject->getId() === (int) $transaction->getId()) {
                    $found = true;
                }
            }
            if (!$found) {
                $order->addRelatedObject($transaction);
            }
        }
        return $transaction;
    }
}
