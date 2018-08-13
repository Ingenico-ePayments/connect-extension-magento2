<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\EInvoicePaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class EInvoiceDecorator
 */
class EInvoiceDecorator extends AbstractMethodDecorator
{
    /**
     * @var EInvoicePaymentMethodSpecificInputFactory
     */
    private $eInvoiceTransferPaymentMethodSpecificInputFactory;

    /**
     * EInvoiceDecorator constructor.
     *
     * @param EInvoicePaymentMethodSpecificInputFactory $eInvoiceTransferPaymentMethodSpecificInputFactory
     */
    public function __construct(
        EInvoicePaymentMethodSpecificInputFactory $eInvoiceTransferPaymentMethodSpecificInputFactory
    ) {
        $this->eInvoiceTransferPaymentMethodSpecificInputFactory = $eInvoiceTransferPaymentMethodSpecificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->eInvoiceTransferPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $this->getProductId($order);

        $request->eInvoicePaymentMethodSpecificInput = $input;

        return $request;
    }
}
