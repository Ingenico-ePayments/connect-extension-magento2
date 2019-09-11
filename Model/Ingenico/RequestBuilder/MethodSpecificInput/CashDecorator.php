<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CashPaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class CashDecorator
 */
class CashDecorator implements DecoratorInterface
{
    /**
     * @var CashPaymentMethodSpecificInputFactory
     */
    private $cashTransferPaymentMethodSpecificInputFactory;

    /**
     * CashDecorator constructor.
     *
     * @param CashPaymentMethodSpecificInputFactory $cashTransferPaymentMethodSpecificInputFactory
     */
    public function __construct(
        CashPaymentMethodSpecificInputFactory $cashTransferPaymentMethodSpecificInputFactory
    ) {
        $this->cashTransferPaymentMethodSpecificInputFactory = $cashTransferPaymentMethodSpecificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->cashTransferPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);
        $request->cashPaymentMethodSpecificInput = $input;

        return $request;
    }
}
