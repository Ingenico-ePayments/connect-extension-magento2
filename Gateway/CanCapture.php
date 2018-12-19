<?php

namespace Netresearch\Epayments\Gateway;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;

class CanCapture extends AbstractValueHandler
{
    public function handle(array $subject, $storeId = null)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $subject['payment']->getPayment();
        if ($payment->getOrder()->getEntityId() === null) {
            // allow capture for new orders
            return true;
        }

        return parent::handle($subject, $storeId);
    }

    /**
     * @param Payment $paymentResponse
     * @return bool
     */
    protected function getResponseValue($paymentResponse)
    {
        return ($paymentResponse->statusOutput->isAuthorized &&
                $paymentResponse->status !== StatusInterface::CAPTURE_REQUESTED);
    }
}
