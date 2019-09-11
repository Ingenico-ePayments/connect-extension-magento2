<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\InvoicePaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class InvoiceDecorator
 */
class InvoiceDecorator implements DecoratorInterface
{
    /**
     * @var invoicePaymentMethodSpecificInputFactory
     */
    private $invoiceTransferPaymentMethodSpecificInputFactory;

    /**
     * InvoiceDecorator constructor.
     *
     * @param InvoicePaymentMethodSpecificInputFactory $invoiceTransferPaymentMethodSpecificInputFactory
     */
    public function __construct(
        InvoicePaymentMethodSpecificInputFactory $invoiceTransferPaymentMethodSpecificInputFactory
    ) {
        $this->invoiceTransferPaymentMethodSpecificInputFactory = $invoiceTransferPaymentMethodSpecificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->invoiceTransferPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);

        $request->invoicePaymentMethodSpecificInput = $input;

        return $request;
    }
}
