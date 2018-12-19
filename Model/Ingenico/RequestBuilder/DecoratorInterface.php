<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Magento\Sales\Api\Data\OrderInterface;

interface DecoratorInterface
{
    /**
     * @param CreateHostedCheckoutRequest|CreatePaymentRequest|DataObject $request
     * @param OrderInterface $order
     * @return CreateHostedCheckoutRequest|CreatePaymentRequest - updated Request
     */
    public function decorate(DataObject $request, OrderInterface $order);
}
