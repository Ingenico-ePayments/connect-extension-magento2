<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\BankTransferPaymentMethodSpecificInputFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Config;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

/**
 * Class ankTransferDecorator
 */
class BankTransferDecorator implements DecoratorInterface
{
    /**
     * @var BankTransferPaymentMethodSpecificInputFactory
     */
    private $bankTransferPaymentMethodSpecificInputFactory;

    /**
     * BankTransferDecorator constructor.
     *
     * @param BankTransferPaymentMethodSpecificInputFactory $bankTransferPaymentMethodSpecificInputFactory
     */
    public function __construct(
        BankTransferPaymentMethodSpecificInputFactory $bankTransferPaymentMethodSpecificInputFactory
    ) {
        $this->bankTransferPaymentMethodSpecificInputFactory = $bankTransferPaymentMethodSpecificInputFactory;
    }

    /**
     * @inheritdoc
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        $input = $this->bankTransferPaymentMethodSpecificInputFactory->create();
        $input->paymentProductId = $order->getPayment()->getAdditionalInformation(Config::PRODUCT_ID_KEY);

        $request->bankTransferPaymentMethodSpecificInput = $input;

        return $request;
    }
}
