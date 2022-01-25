<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\SepaDirectDebitPaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class SepaDirectDebitDecorator
 */
class SepaDirectDebitDecorator implements DecoratorInterface
{
    /**
     * @var SepaDirectDebitPaymentMethodSpecificInputFactory
     */
    private $specificInputFactory;

    /**
     * SepaDirectDebitDecorator constructor.
     *
     * @param SepaDirectDebitPaymentMethodSpecificInputFactory $specificInputFactory
     */
    public function __construct(SepaDirectDebitPaymentMethodSpecificInputFactory $specificInputFactory)
    {
        $this->specificInputFactory = $specificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->specificInputFactory->create();
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);

        $input->tokenize = false;

        $request->sepaDirectDebitPaymentMethodSpecificInput = $input;

        return $request;
    }
}
