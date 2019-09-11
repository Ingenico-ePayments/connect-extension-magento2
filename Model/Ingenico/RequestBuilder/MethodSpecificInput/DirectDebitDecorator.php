<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\NonSepaDirectDebitPaymentMethodSpecificInput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\NonSepaDirectDebitPaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class DirectDebitDecorator
 */
class DirectDebitDecorator implements DecoratorInterface
{
    /**
     * @var NonSepaDirectDebitPaymentMethodSpecificInputFactory
     */
    private $specificInputFactory;

    /**
     * DirectDebitDecorator constructor.
     *
     * @param NonSepaDirectDebitPaymentMethodSpecificInputFactory $specificInputFactory
     */
    public function __construct(
        NonSepaDirectDebitPaymentMethodSpecificInputFactory $specificInputFactory
    ) {
        $this->specificInputFactory = $specificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        /** @var NonSepaDirectDebitPaymentMethodSpecificInput $input */
        $input = $this->specificInputFactory->create();
        $input->paymentProductId = $this->getProductId($order);
        $input->directDebitText = $order->getIncrementId();
        $tokenize = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY);
        $input->tokenize = ($tokenize === '1');

        $request->directDebitPaymentMethodSpecificInput = $input;

        return $request;
    }

    /**
     * @param OrderInterface $order
     * @return string|null
     */
    private function getProductId(OrderInterface $order)
    {
        return $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);
    }
}
