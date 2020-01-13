<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\CustomerBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\ShippingBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\ShoppingCartBuilder;
use Ingenico\Connect\Sdk\Domain\Definitions\AmountOfMoneyFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AdditionalOrderInputFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferencesFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderTypeInformationFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;

/**
 * Class OrderBuilder
 */
class OrderBuilder
{
    /**
     * @var ConfigInterface
     */
    private $ePaymentsConfig;

    /**
     * @var CustomerBuilder
     */
    private $customerBuilder;

    /**
     * @var ShoppingCartBuilder
     */
    private $shoppingCartBuilder;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var AmountOfMoneyFactory
     */
    private $amountOfMoneyFactory;

    /**
     * @var OrderReferencesFactory
     */
    private $orderReferencesFactory;

    /**
     * @var AdditionalOrderInputFactory
     */
    private $additionalOrderInputFactory;

    /**
     * @var OrderTypeInformationFactory
     */
    private $orderTypeInformationFactory;

    /**
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * @var ShippingBuilder
     */
    private $shippingBuilder;

    /**
     * OrderBuilder constructor.
     *
     * @param ConfigInterface $ePaymentsConfig
     * @param CustomerBuilder $customerBuilder
     * @param ShoppingCartBuilder $shoppingCartBuilder
     * @param DateTime $dateTime
     * @param OrderFactory $orderFactory
     * @param AmountOfMoneyFactory $amountOfMoneyFactory
     * @param OrderReferencesFactory $orderReferencesFactory
     * @param AdditionalOrderInputFactory $additionalOrderInputFactory
     * @param OrderTypeInformationFactory $orderTypeInformationFactory
     * @param MerchantReference $merchantReference
     */
    public function __construct(
        ConfigInterface $ePaymentsConfig,
        CustomerBuilder $customerBuilder,
        ShoppingCartBuilder $shoppingCartBuilder,
        DateTime $dateTime,
        OrderFactory $orderFactory,
        AmountOfMoneyFactory $amountOfMoneyFactory,
        OrderReferencesFactory $orderReferencesFactory,
        AdditionalOrderInputFactory $additionalOrderInputFactory,
        OrderTypeInformationFactory $orderTypeInformationFactory,
        ShippingBuilder $shippingBuilder,
        MerchantReference $merchantReference
    ) {
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->customerBuilder = $customerBuilder;
        $this->shoppingCartBuilder = $shoppingCartBuilder;
        $this->dateTime = $dateTime;
        $this->orderFactory = $orderFactory;
        $this->amountOfMoneyFactory = $amountOfMoneyFactory;
        $this->orderReferencesFactory = $orderReferencesFactory;
        $this->additionalOrderInputFactory = $additionalOrderInputFactory;
        $this->orderTypeInformationFactory = $orderTypeInformationFactory;
        $this->merchantReference = $merchantReference;
        $this->shippingBuilder = $shippingBuilder;
    }

    /**
     * @param Order $order
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Order
     */
    public function create(Order $order)
    {
        $ingenicoOrder = $this->orderFactory->create();

        $ingenicoOrder->amountOfMoney = $this->getAmountOfMoney($order);
        $ingenicoOrder->customer = $this->customerBuilder->create($order);

        $ingenicoOrder->shoppingCart = $this->shoppingCartBuilder->create($order);
        $ingenicoOrder->references = $this->getReferences($order);
        $ingenicoOrder->additionalInput = $this->getAdditionalInput($order);
        $ingenicoOrder->shipping = $this->shippingBuilder->create($order);

        return $ingenicoOrder;
    }

    /**
     * @param Order $order
     * @return \Ingenico\Connect\Sdk\Domain\Definitions\AmountOfMoney
     */
    private function getAmountOfMoney(Order $order)
    {
        $amountOfMoney = $this->amountOfMoneyFactory->create();
        $amountOfMoney->amount = DataHelper::formatIngenicoAmount($order->getBaseGrandTotal());
        $amountOfMoney->currencyCode = $order->getBaseCurrencyCode();

        return $amountOfMoney;
    }

    /**
     * @param Order $order
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferences
     */
    private function getReferences(Order $order)
    {
        $references = $this->orderReferencesFactory->create();
        $references->merchantReference = $this->merchantReference->generateMerchantReference($order);
        $references->descriptor = $this->ePaymentsConfig->getDescriptor();

        return $references;
    }

    /**
     * @param Order $order
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\AdditionalOrderInput
     */
    private function getAdditionalInput(Order $order)
    {
        $additionalInput = $this->additionalOrderInputFactory->create();

        $additionalInput->orderDate = $this->dateTime->date('YmdHis', strtotime($order->getCreatedAt()));

        $typeInformation = $this->orderTypeInformationFactory->create();
        $typeInformation->purchaseType = 'good';
        $typeInformation->usageType = 'commercial';
        $additionalInput->typeInformation = $typeInformation;

        return $additionalInput;
    }
}
