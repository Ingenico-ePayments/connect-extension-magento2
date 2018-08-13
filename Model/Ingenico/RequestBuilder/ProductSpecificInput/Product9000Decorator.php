<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\EInvoicePaymentProduct9000SpecificInput;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\EInvoicePaymentProduct9000SpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class Product705Decorator
 * @package Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput
 */
class Product9000Decorator implements DecoratorInterface
{

    /** @var EInvoicePaymentProduct9000SpecificInputFactory */
    private $inputFactory;

    /**
     * Product705Decorator constructor.
     * @param EInvoicePaymentProduct9000SpecificInputFactory $inputFactory
     */
    public function __construct(EInvoicePaymentProduct9000SpecificInputFactory $inputFactory)
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
        /** @var EInvoicePaymentProduct9000SpecificInput $input */
        $input = $this->inputFactory->create();
        $request->eInvoicePaymentMethodSpecificInput->paymentProduct9000SpecificInput = $input;
        return $request;
    }
}
