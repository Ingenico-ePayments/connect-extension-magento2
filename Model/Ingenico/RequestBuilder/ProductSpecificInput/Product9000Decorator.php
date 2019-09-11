<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\ProductSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\EInvoicePaymentProduct9000SpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class Product9000Decorator
 *
 * @package Ingenico\Connect\Model\Ingenico\RequestBuilder\ProductSpecificInput
 */
class Product9000Decorator implements DecoratorInterface
{

    /**
     * @var EInvoicePaymentProduct9000SpecificInputFactory
     */
    private $inputFactory;

    /**
     * Product9000Decorator constructor.
     *
     * @param EInvoicePaymentProduct9000SpecificInputFactory $inputFactory
     */
    public function __construct(EInvoicePaymentProduct9000SpecificInputFactory $inputFactory)
    {
        $this->inputFactory = $inputFactory;
    }

    /**
     * @param DataObject|CreateHostedCheckoutRequest|CreatePaymentRequest $request
     * @param OrderInterface $order
     * @return CreateHostedCheckoutRequest|CreatePaymentRequest
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->inputFactory->create();
        $request->eInvoicePaymentMethodSpecificInput->paymentProduct9000SpecificInput = $input;

        return $request;
    }
}
