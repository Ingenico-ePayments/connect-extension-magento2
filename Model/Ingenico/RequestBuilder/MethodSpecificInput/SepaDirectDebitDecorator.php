<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\SepaDirectDebitPaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Config;

/**
 * Class SepaDirectDebitDecorator
 */
class SepaDirectDebitDecorator extends AbstractMethodDecorator
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
    public function __construct(
        SepaDirectDebitPaymentMethodSpecificInputFactory $specificInputFactory
    ) {
        $this->specificInputFactory = $specificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->specificInputFactory->create();
        $input->paymentProductId = $this->getProductId($order);

        $tokenize = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY);
        $input->tokenize = ($tokenize === '1');

        $request->sepaDirectDebitPaymentMethodSpecificInput = $input;

        return $request;
    }
}
