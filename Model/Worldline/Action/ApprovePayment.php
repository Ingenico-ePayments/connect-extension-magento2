<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\Action;

// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse
use Ingenico\Connect\Sdk\Domain\Payment\ApprovePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferencesApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment as PaymentResponse;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Worldline\Connect\Helper\Data;
use Worldline\Connect\Model\ConfigInterface;
use Worldline\Connect\Model\StatusResponseManager;
use Worldline\Connect\Model\Transaction\TransactionManager;
use Worldline\Connect\Model\Worldline\Api\ClientInterface;
use Worldline\Connect\Model\Worldline\MerchantReference;
use Worldline\Connect\Model\Worldline\Status\OrderStatusHelper;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__approve_post
 */
class ApprovePayment extends AbstractAction implements ActionInterface
{
    /**
     * @var ApprovePaymentRequest
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $approvePaymentRequest;

    /**
     * @var OrderApprovePayment
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderApprovePayment;

    /**
     * @var OrderReferencesApprovePayment
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderReferencesApprovePayment;

    /**
     * @var MerchantReference
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $merchantReference;

    /**
     * @var OrderStatusHelper
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderstatusHelper;

    /**
     * ApprovePayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $worldlineClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ApprovePaymentRequest $approvePaymentRequest
     * @param OrderApprovePayment $orderApprovePayment
     * @param OrderReferencesApprovePayment $orderReferencesApprovePayment
     * @param MerchantReference $merchantReference
     * @param OrderStatusHelper $orderStatusHelper
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $worldlineClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ApprovePaymentRequest $approvePaymentRequest,
        OrderApprovePayment $orderApprovePayment,
        OrderReferencesApprovePayment $orderReferencesApprovePayment,
        MerchantReference $merchantReference,
        OrderStatusHelper $orderStatusHelper
    ) {
        $this->approvePaymentRequest = $approvePaymentRequest;
        $this->orderApprovePayment = $orderApprovePayment;
        $this->orderReferencesApprovePayment = $orderReferencesApprovePayment;
        $this->merchantReference = $merchantReference;
        $this->orderstatusHelper = $orderStatusHelper;

        parent::__construct(
            $statusResponseManager,
            $worldlineClient,
            $transactionManager,
            $config
        );
    }

    /**
     * @param Order $order
     * @throws LocalizedException
     */
    public function process(Order $order)
    {
        $response = $this->approvePayment($order);

        /** @var Payment $payment */
        $payment = $order->getPayment();
        $payment->registerCaptureNotification(
            Data::reformatMagentoAmount($response->paymentOutput->amountOfMoney->amount)
        );

        if (!$this->orderstatusHelper->shouldOrderSkipPaymentReview($response)) {
            // move order into payment_review
            // set transaction 'is_closed' to 0
            $payment->setIsTransactionClosed(false);
            // set order status to 'Payment Review'
            $payment->setIsTransactionPending(true);
        } else {
            /** @var Order\Invoice $invoice */
            foreach ($order->getInvoiceCollection() as $invoice) {
                // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
                if ($invoice->getTransactionId() == $response->id) {
                    $invoice->setState(Order\Invoice::STATE_OPEN);
                }
            }
        }

        $payment->setPreparedMessage(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            sprintf(
                'Successfully processed notification about status %s with statusCode %s.',
                $response->status,
                $response->statusOutput->statusCode
            )
        );

        $this->postProcess($payment, $response);
    }

    private function approvePayment(Order $order): PaymentResponse
    {
        $payment = $order->getPayment();
        $this->orderReferencesApprovePayment->merchantReference = $this->merchantReference
            ->generateMerchantReferenceForOrder($order);

        $this->orderApprovePayment->references = $this->orderReferencesApprovePayment;

        $this->approvePaymentRequest->order = $this->orderApprovePayment;

        $response = $this->worldlineClient->worldlinePaymentApprove(
            $payment->getLastTransId(),
            $this->approvePaymentRequest,
            $order->getStoreId()
        );

        return $response->payment;
    }
}
