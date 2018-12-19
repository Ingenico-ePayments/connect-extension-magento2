<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

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
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\ConfigProvider;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\Status\ResolverInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__get
 */
class RetrievePayment extends AbstractAction implements ActionInterface
{
    /**
     * @var ResolverInterface
     */
    private $statusResolver;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var GetHostedCheckoutStatus
     */
    private $getHostedCheckoutStatus;

    /**
     * RetrievePayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ResolverInterface $statusResolver
     * @param OrderRepositoryInterface $orderRepository
     * @param GetHostedCheckoutStatus $getHostedCheckoutStatus
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ResolverInterface $statusResolver,
        OrderRepositoryInterface $orderRepository,
        GetHostedCheckoutStatus $getHostedCheckoutStatus
    ) {
        $this->statusResolver = $statusResolver;
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

        $ingenicoPaymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        if (!$ingenicoPaymentId) {
            try {
                $orderWasUpdated = $this->updateHostedCheckoutStatus($order);
            } catch (\Exception $e) {
                throw new LocalizedException(__('Order is not linked with Ingenico ePayments orders.'));
            }
        }

        $orderTransactions = $this->transactionManager->retrieveTransactions($payment);
        foreach ($orderTransactions as $transaction) {
            $currentStatus = $this->getCurrentStatus($payment, $transaction);
            $updateStatus = $this->getUpdateStatus($order, $currentStatus);
            if ($updateStatus->status !== $currentStatus->status) {
                $this->statusResolver->resolve($order, $updateStatus);

                /** @var Payment\Transaction $transaction */
                $transaction = $this->transactionManager->retrieveTransaction($transaction->getTxnId());
                if ($transaction !== null) {
                    $order->addRelatedObject($transaction);
                }
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
     * @param IngenicoPayment|RefundResult|Capture$currentStatus
     * @return \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse|PaymentResponse|RefundResponse
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
     * @throws \Exception
     */
    private function updateHostedCheckoutStatus(Order $order)
    {
        $hostedCheckoutId = $order->getPayment()->getAdditionalInformation(Config::HOSTED_CHECKOUT_ID_KEY);
        $order = $this->getHostedCheckoutStatus->process($hostedCheckoutId);
        $ingenicoPaymentId = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        return $ingenicoPaymentId !== null;
    }
}
