<?php

namespace Netresearch\Epayments\Gateway;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment;
use Netresearch\Epayments\Model\Ingenico\StatusInterface;

class CanReviewPayment extends AbstractValueHandler
{
    /**
     * @param Payment $paymentResponse
     * @return bool
     */
    protected function getResponseValue($paymentResponse)
    {
        switch ($paymentResponse->status) {
            case StatusInterface::PENDING_CAPTURE:
                $result = true;
                break;
            case StatusInterface::AUTHORIZATION_REQUESTED:
                $result = false;
                break;
            default:
                $result = false;
        }

        return $result;
    }
}
