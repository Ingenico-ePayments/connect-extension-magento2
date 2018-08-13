<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\NonSepaDirectDebitPaymentMethodSpecificInput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\NonSepaDirectDebitPaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Config;

/**
 * Class DirectDebitDecorator
 */
class DirectDebitDecorator extends AbstractMethodDecorator
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
}
