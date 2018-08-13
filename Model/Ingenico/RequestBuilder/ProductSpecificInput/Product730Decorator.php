<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\NonSepaDirectDebitPaymentProduct730SpecificInput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\NonSepaDirectDebitPaymentProduct730SpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class Product730Decorator
 * @package Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput
 */
class Product730Decorator implements DecoratorInterface
{

    /** @var NonSepaDirectDebitPaymentProduct730SpecificInputFactory */
    private $inputFactory;

    /**
     * Product730Decorator constructor.
     * @param NonSepaDirectDebitPaymentProduct730SpecificInputFactory $inputFactory
     */
    public function __construct(NonSepaDirectDebitPaymentProduct730SpecificInputFactory $inputFactory)
    {
        $this->inputFactory = $inputFactory;
    }

    /**
     * @param DataObject $request
     * @param OrderInterface $order
     * @return DataObject|CreateHostedCheckoutRequest|CreatePaymentRequest
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        /** @var CreateHostedCheckoutRequest|CreatePaymentRequest $request */
        /** @var NonSepaDirectDebitPaymentProduct730SpecificInput $input */
        $input = $this->inputFactory->create();
        $request->directDebitPaymentMethodSpecificInput->paymentProduct730SpecificInput = $input;

        return $request;
    }
}
