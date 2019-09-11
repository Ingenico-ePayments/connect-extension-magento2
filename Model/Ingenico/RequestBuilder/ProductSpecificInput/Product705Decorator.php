<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\ProductSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\NonSepaDirectDebitPaymentProduct705SpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class Product705Decorator
 *
 * @package Ingenico\Connect\Model\Ingenico\RequestBuilder\ProductSpecificInput
 */
class Product705Decorator implements DecoratorInterface
{

    /** @var NonSepaDirectDebitPaymentProduct705SpecificInputFactory */
    private $inputFactory;

    /**
     * Product705Decorator constructor.
     *
     * @param NonSepaDirectDebitPaymentProduct705SpecificInputFactory $inputFactory
     */
    public function __construct(NonSepaDirectDebitPaymentProduct705SpecificInputFactory $inputFactory)
    {
        $this->inputFactory = $inputFactory;
    }

    /**
     * @param CreateHostedCheckoutRequest|CreatePaymentRequest|DataObject $request
     * @param OrderInterface $order
     * @return DataObject|CreateHostedCheckoutRequest|CreatePaymentRequest
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->inputFactory->create();
        $input->transactionType = 'first-payment';
        $request->directDebitPaymentMethodSpecificInput->paymentProduct705SpecificInput = $input;

        return $request;
    }
}
