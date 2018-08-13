<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferencesApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\ApprovePaymentRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Helper\Data as DataHelper;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__approve_post
 */
class ApprovePayment extends AbstractAction implements ActionInterface
{
    /** @var ApprovePaymentRequest */
    private $approvePaymentRequest;

    /** @var OrderApprovePayment */
    private $orderApprovePayment;

    /** @var  OrderReferencesApprovePayment */
    private $orderReferencesApprovePayment;

    /**
     * CapturePayment constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param ClientInterface $ingenicoClient
     * @param TransactionManager $transactionManager
     * @param ConfigInterface $config
     * @param ApprovePaymentRequest $approvePaymentRequest
     * @param OrderApprovePayment $orderApprovePayment
     * @param OrderReferencesApprovePayment $orderReferencesApprovePayment
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ApprovePaymentRequest $approvePaymentRequest,
        OrderApprovePayment $orderApprovePayment,
        OrderReferencesApprovePayment $orderReferencesApprovePayment
    ) {
        $this->approvePaymentRequest = $approvePaymentRequest;
        $this->orderApprovePayment = $orderApprovePayment;
        $this->orderReferencesApprovePayment = $orderReferencesApprovePayment;

        parent::__construct(
            $statusResponseManager,
            $ingenicoClient,
            $transactionManager,
            $config
        );
    }

    /**
     * Approve payment with Ingenico
     *
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

        if ($response->status === StatusInterface::CAPTURE_REQUESTED) {
            $payment->setIsTransactionClosed(false); // set transaction 'is_closed' to 0
            $payment->setIsTransactionPending(true); // set order status to 'Payment Review'
        }

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
    private function approvePayment(
        $ingenicoPaymentId,
        Payment $payment,
        $amount
    ) {
        $request = $this->approvePaymentRequest;
        $approveOrderReferences = $this->orderReferencesApprovePayment;
        $approveOrder = $this->orderApprovePayment;

        $approveOrderReferences->merchantReference = $payment->getOrder()->getIncrementId();

        $approveOrder->references = $approveOrderReferences;

        $request->order = $approveOrder;
        $request->amount = DataHelper::formatIngenicoAmount($amount);

        $response = $this->ingenicoClient->ingenicoPaymentApprove(
            $ingenicoPaymentId,
            $request,
            $payment->getOrder()->getStoreId()
        );

        return $response->payment;
    }
}
