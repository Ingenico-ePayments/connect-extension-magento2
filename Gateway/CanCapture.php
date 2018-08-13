<?php

namespace Netresearch\Epayments\Gateway;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;

class CanCapture extends AbstractValueHandler
{
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
