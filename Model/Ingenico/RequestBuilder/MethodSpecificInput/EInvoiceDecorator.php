<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\EInvoicePaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class EInvoiceDecorator
 */
class EInvoiceDecorator implements DecoratorInterface
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
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);

        $request->eInvoicePaymentMethodSpecificInput = $input;

        return $request;
    }
}
