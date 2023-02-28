<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

use Exception;
use Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse;
use Ingenico\Connect\Sdk\Domain\Capture\Definitions\Capture;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as WorldlinePayment;
use Ingenico\Connect\Sdk\Domain\Payment\PaymentResponse;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundResult;
use Ingenico\Connect\Sdk\Domain\Refund\RefundResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Model\Config;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\Status\Payment\ResolverInterface as PaymentResolverInterface;
use Worldline\Connect\Model\Worldline\Status\Refund\ResolverInterface as RefundResolverInterface;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__get
 */
class RetrievePayment extends AbstractAction implements ActionInterface
{
    /**
     * @var PaymentResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $paymentStatusResolver;

    /**
     * @var OrderRepositoryInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderRepository;

    /**
     * @var GetHostedCheckoutStatus
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $getHostedCheckoutStatus;

    /**
     * @var RefundResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $refundStatusResolver;

    /**
     * RetrievePayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $worldlineClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param PaymentResolverInterface $paymentStatusResolver
     * @param RefundResolverInterface $refundStatusResolver
     * @param OrderRepositoryInterface $orderRepository
     * @param GetHostedCheckoutStatus $getHostedCheckoutStatus
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
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

        parent::__construct($statusResponseManager, $worldlineClient, $transactionManager, $config);
    }

    /**
     * Will retrieve updates for all transactions/objects related to the order (payment, capture, refund)
     *
     * @param Order $order
     * @return bool
     * @throws LocalizedException
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function process(Order $order)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();

        $orderWasUpdated = false;
        $orderTransactions = $this->transactionManager->retrieveTransactions($payment);

        $worldlinePaymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        if (!$worldlinePaymentId && empty($orderTransactions)) {
            try {
                return $this->updateHostedCheckoutStatus($order);
            } catch (Exception $e) {
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                throw new LocalizedException(__('Order is not linked with Worldline ePayments orders.'));
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

                // phpcs:ignore Generic.Commenting.Todo.TaskFound
                // @TODO: this looks like an ugly fix. Extend the integration test suite to check if this is required:
                // phpcs:ignore Generic.Commenting.Todo.TaskFound
                // @TODO: this only needs to be tested for UndoCapturePaymentObserver
                /** @var Payment\Transaction $transaction */
                $this->addTransactionToOrder($order, $transaction);
                // phpcs:ignore Generic.Commenting.Todo.TaskFound
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
     * @return false|WorldlinePayment
     */
    private function getCurrentStatus($payment, $transaction)
    {
        $currentStatus = $this->statusResponseManager->get($payment, $transaction->getTxnId());

        return $currentStatus;
    }

    /**
     * @param Order $order
     * @param WorldlinePayment|RefundResult|Capture $currentStatus
     * @return CaptureResponse|PaymentResponse|RefundResponse
     * @throws LocalizedException
     */
    private function getUpdateStatus(Order $order, $currentStatus)
    {
        $merchant = $this->worldlineClient
            ->getWorldlineClient($order->getStoreId())
            ->merchant($this->config->getMerchantId($order->getStoreId()));

        if ($currentStatus instanceof RefundResult) {
            $response = $merchant->refunds()->get($currentStatus->id);
        } elseif ($currentStatus instanceof WorldlinePayment) {
            $response = $merchant->payments()->get($currentStatus->id);
        } elseif ($currentStatus instanceof Capture) {
            $response = $merchant->captures()->get($currentStatus->id);
        } else {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
        $worldlinePaymentId = $order->getPayment()->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        return $worldlinePaymentId !== null;
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

    // phpcs:disable Generic.Commenting.Fixme.CommentFound
    /**
     * @fixme: this method can be most likely be removed entirely as soon
     * as the integration test also covers UndoCapturePaymentObserver
     * @param Order $order
     * @param Payment\Transaction $transaction
     * @return Payment\Transaction|null
     */
    // phpcs:enable Generic.Commenting.Fixme.CommentFound
    private function addTransactionToOrder(Order $order, Payment\Transaction $transaction)
    {
        $transaction = $this->transactionManager->retrieveTransaction($transaction->getTxnId());
        if ($transaction !== null) {
            $found = false;
            foreach ($order->getRelatedObjects() as $relatedObject) {
                // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.FirstExpressionLine
                if ($relatedObject instanceof Payment\Transaction &&
                    // phpcs:ignore PSR12.ControlStructures.ControlStructureSpacing.CloseParenthesisLine
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
