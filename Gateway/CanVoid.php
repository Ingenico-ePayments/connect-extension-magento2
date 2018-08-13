<?php

namespace Netresearch\Epayments\Gateway;

class CanVoid extends AbstractValueHandler
{
    /**
     * @param \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Payment $paymentResponse
     * @return mixed
     */
    protected function getResponseValue($paymentResponse)
    {
        return $paymentResponse->statusOutput->isCancellable;
    }
}
