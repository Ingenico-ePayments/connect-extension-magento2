<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class AbstractMethodDecorator
 */
abstract class AbstractMethodDecorator implements DecoratorInterface
{
    /**
     * @param CreateHostedCheckoutRequest|CreatePaymentRequest $request
     * @param OrderInterface $order
     * @return CreateHostedCheckoutRequest|CreatePaymentRequest
     */
    abstract public function decorate(DataObject $request, OrderInterface $order);

    /**
     * @param OrderInterface $order
     * @return string|null
     */
    protected function getProductId(OrderInterface $order)
    {
        return $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);
    }
}
