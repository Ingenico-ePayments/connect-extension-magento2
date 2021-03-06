<?php

namespace Ingenico\Connect\Gateway;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Ingenico\Connect\Model\Ingenico\StatusInterface;

class CanCapturePartial extends AbstractValueHandler
{
    /**
     * @param Payment $paymentResponse
     * @return bool
     */
    protected function getResponseValue($paymentResponse)
    {
        return $paymentResponse->status === StatusInterface::PENDING_CAPTURE;
    }
}
