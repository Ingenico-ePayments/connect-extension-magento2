<?php

namespace Ingenico\Connect\Gateway;

use Magento\Framework\Registry;
use Magento\Payment\Gateway\Config\ValueHandlerInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Payment;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\StatusInterface;
use Ingenico\Connect\Model\StatusResponseManager;

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
        /** @var Payment $payment */
        $payment = $subject['payment']->getPayment();
        $paymentId = $payment->getAdditionalInformation(Config::PAYMENT_ID_KEY);

        /** @var Creditmemo $creditmemo */
        $creditmemo = $this->registry->registry('current_creditmemo');

        if ($creditmemo &&
            $creditmemo->getTransactionId() &&
            $refundResponse = $this->statusResponseManager->get($payment, $creditmemo->getTransactionId())
        ) {
            return $refundResponse->status == StatusInterface::PENDING_APPROVAL;
        }

        if (($paymentResponse = $this->statusResponseManager->get($payment, $paymentId)) &&
            isset($paymentResponse->statusOutput->isRefundable)
        ) {
            return $paymentResponse->statusOutput->isRefundable;
        }

        return false;
    }
}
