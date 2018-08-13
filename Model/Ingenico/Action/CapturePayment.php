<?php

namespace Netresearch\Epayments\Model\Ingenico\Action;

use Ingenico\Connect\Sdk\Domain\Payment\CapturePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferencesApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderApprovePayment;
use Ingenico\Connect\Sdk\Domain\Payment\ApprovePaymentRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Helper\Data;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\ConfigInterface;
use Netresearch\Epayments\Model\Ingenico\Api\ClientInterface;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;
use Netresearch\Epayments\Model\Transaction\TransactionManager;
use Netresearch\Epayments\Helper\Data as DataHelper;

/**
 * @link https://developer.globalcollect.com/documentation/api/server/#__merchantId__payments__paymentId__approve_post
 */
class CapturePayment extends AbstractAction implements ActionInterface
{
    /** @var ApprovePaymentRequest */
    private $approvePaymentRequest;

    /** @var CapturePaymentRequest */
    private $capturePaymentRequest;

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
     * @param CapturePaymentRequest $capturePaymentRequest
     * @param OrderApprovePayment $orderApprovePayment
     * @param OrderReferencesApprovePayment $orderReferencesApprovePayment
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        ClientInterface $ingenicoClient,
        TransactionManager $transactionManager,
        ConfigInterface $config,
        ApprovePaymentRequest $approvePaymentRequest,
        CapturePaymentRequest $capturePaymentRequest,
        OrderApprovePayment $orderApprovePayment,
        OrderReferencesApprovePayment $orderReferencesApprovePayment
    ) {
        $this->approvePaymentRequest = $approvePaymentRequest;
        $this->capturePaymentRequest = $capturePaymentRequest;
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
     * Capture payment with Ingenico
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

        $authResponseObject = $this->statusResponseManager->get($payment, $ingenicoPaymentId);

        $ingenicoPaymentId = $authResponseObject->id;

        $response = $this->capturePayment($ingenicoPaymentId, $payment, $amount);

        if ($response->status === StatusInterface::CAPTURE_REQUESTED) {
                $payment->setIsTransactionPending(true); // set order status to 'Payment Review'
        }

        $this->postProcess($payment, $response);
    }

    /**
     * Capture payments via Ogone api.
     * With no further settings the request will always capture
     * the full amount
     *
     * @param string $ingenicoPaymentId
     * @param Payment $payment
     * @param int $amount
     * @param bool $isFinal
     * @return \Ingenico\Connect\Sdk\Domain\Capture\CaptureResponse
     */
    private function capturePayment(
        $ingenicoPaymentId,
        Payment $payment,
        $amount,
        $isFinal = false
    ) {
        $request = $this->capturePaymentRequest;
        $request->amount = DataHelper::formatIngenicoAmount($amount);
        $request->isFinal = $isFinal;

        $response = $this->ingenicoClient->ingenicoPaymentCapture(
            $ingenicoPaymentId,
            $request,
            $payment->getOrder()->getStoreId()
        );

        return $response;
    }
}
