<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CashPaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class CashDecorator
 */
class CashDecorator extends AbstractMethodDecorator
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
        $input->paymentProductId = $this->getProductId($order);

        $request->cashPaymentMethodSpecificInput = $input;

        return $request;
    }
}
