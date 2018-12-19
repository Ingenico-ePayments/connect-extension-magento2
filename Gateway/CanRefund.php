<?php

namespace Netresearch\Epayments\Gateway;

use Magento\Framework\Registry;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;
use Netresearch\Epayments\Model\StatusResponseManager;

class CanRefund implements ValueHandlerInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var StatusResponseManager
     */
    private $statusResponseManager;

    /**
     * CanRefund constructor.
     *
     * @param StatusResponseManager $statusResponseManager
     * @param Registry $registry
     */
    public function __construct(
        StatusResponseManager $statusResponseManager,
        Registry $registry
    ) {
        $this->statusResponseManager = $statusResponseManager;
        $this->registry = $registry;
    }

    /**
     * Check if refund can be created online
     *
     * @param array $subject
     * @param null $storeId
     * @return bool
     */
    public function handle(array $subject, $storeId = null)
    {
        $result = false;

        /** @var Payment $payment */
        $payment = $subject['payment']->getPayment();
        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        /** @var Creditmemo $creditmemo */
        $creditmemo = $this->registry->registry('current_creditmemo');

        if ($creditmemo &&
            $creditmemo->getTransactionId() &&
            $refundResponse = $this->statusResponseManager->get($payment, $creditmemo->getTransactionId())
        ) {
            $result = $refundResponse->status == StatusInterface::PENDING_APPROVAL;
        } elseif (($paymentResponse = $this->statusResponseManager->get($payment, $paymentId)) &&
                  isset($paymentResponse->statusOutput->isRefundable)
        ) {
            $result = $paymentResponse->statusOutput->isRefundable;
            if (!$result &&
                 $creditmemo &&
                 $creditmemo->getInvoice() &&
                 $invoiceId = $creditmemo->getInvoice()->getTransactionId()
            ) {
                // @FIXME: This is a workaround until the ingenico api always reports the is_refundable property!
                $paymentResponse = $this->statusResponseManager->get($payment, $invoiceId);
                $result = in_array($paymentResponse->statusOutput->statusCode, [9, 95]);
            }
        }

        return $result;
    }
}
