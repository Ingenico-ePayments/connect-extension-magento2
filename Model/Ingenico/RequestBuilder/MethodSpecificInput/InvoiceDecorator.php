<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\InvoicePaymentMethodSpecificInputFactory;

/**
 * Class InvoiceDecorator
 */
class InvoiceDecorator extends AbstractMethodDecorator
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
        $input->paymentProductId = $this->getProductId($order);

        $request->invoicePaymentMethodSpecificInput = $input;

        return $request;
    }
}
