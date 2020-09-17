<?php

namespace Ingenico\Connect\Model\Ingenico\Action;

use Ingenico\Connect\Model\Order\Payment\OrderPaymentManagement;
use Ingenico\Connect\Sdk\Domain\Payment\ApprovePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferencesApprovePayment;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\Api\ClientInterface;
use Ingenico\Connect\Model\Ingenico\GlobalCollect\Status\OrderStatusHelper;
use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Ingenico\Connect\Model\StatusResponseManager;
use Ingenico\Connect\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__approve_post
 */
class ApprovePayment extends AbstractAction implements ActionInterface
{
    /**
     * @var ApprovePaymentRequest
     */
    private $approvePaymentRequest;

    /**
     * @var OrderApprovePayment
     */
    private $orderApprovePayment;

    /**
     * @var OrderReferencesApprovePayment
     */
    private $orderReferencesApprovePayment;

    /**
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * @var OrderStatusHelper
     */
    private $orderstatusHelper;

    /**
     * ApprovePayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
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
        ClientInterface $ingenicoClient,
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
            $ingenicoClient,
            $transactionManager,
            $config
        );
    }

    /**
     * @param Order $order
     * @param $amount
     * @throws LocalizedException
     */
    public function process(Order $order, $amount)
    {
        /** @var Payment $payment */
        $payment = $order->getPayment();

        $ingenicoPaymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);
        $status = $this->statusResponseManager->get($payment, $ingenicoPaymentId);
        $ingenicoPaymentId = $status->id;

        $response = $this->approvePayment($ingenicoPaymentId, $payment, $amount);
        $payment->setAdditionalInformation(
            OrderPaymentManagement::KEY_PAYMENT_STATUS,
            $response->status
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
                if ($invoice->getTransactionId() == $response->id) {
                    $invoice->setState(Order\Invoice::STATE_OPEN);
                }
            }
        }
        $payment->setPreparedMessage(
            sprintf(
                'Successfully processed notification about status %s with statusCode %s.',
                $response->status,
                $response->statusOutput->statusCode
            )
        );

        $this->postProcess($payment, $response);
    }

    /**
     * Approve payments made via global collect api
     *
     * @param $ingenicoPaymentId
     * @param Payment $payment
     * @param $amount
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment
     */
    private function approvePayment($ingenicoPaymentId, Payment $payment, $amount)
    {
        $this->orderReferencesApprovePayment->merchantReference = $this->merchantReference
            ->generateMerchantReferenceForOrder($payment->getOrder());

        $this->orderApprovePayment->references = $this->orderReferencesApprovePayment;

        $this->approvePaymentRequest->order = $this->orderApprovePayment;
        $this->approvePaymentRequest->amount = DataHelper::formatIngenicoAmount($amount);

        $response = $this->ingenicoClient->ingenicoPaymentApprove(
            $ingenicoPaymentId,
            $this->approvePaymentRequest,
            $payment->getOrder()->getStoreId()
        );

        return $response->payment;
    }
}
