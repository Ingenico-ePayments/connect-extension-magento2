<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Helper\Format;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\AdditionalInputBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\CustomerBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\ShippingBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\ShoppingCartBuilder;
use Ingenico\Connect\Sdk\Domain\Definitions\AmountOfMoneyFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderReferencesFactory;
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
     * @var MerchantReference
     */
    private $merchantReference;

    /**
     * @var ShippingBuilder
     */
    private $shippingBuilder;
    /**
     * @var AdditionalInputBuilder
     */
    private $additionalInputBuilder;

    /**
     * @var Format
     */
    private $format;

    /**
     * OrderBuilder constructor.
     *
     * @param ConfigInterface $ePaymentsConfig
     * @param CustomerBuilder $customerBuilder
     * @param ShoppingCartBuilder $shoppingCartBuilder
     * @param OrderFactory $orderFactory
     * @param AmountOfMoneyFactory $amountOfMoneyFactory
     * @param OrderReferencesFactory $orderReferencesFactory
     * @param AdditionalInputBuilder $additionalInputBuilder
     * @param ShippingBuilder $shippingBuilder
     * @param MerchantReference $merchantReference
     * @param Format $format
     */
    public function __construct(
        ConfigInterface $ePaymentsConfig,
        CustomerBuilder $customerBuilder,
        ShoppingCartBuilder $shoppingCartBuilder,
        OrderFactory $orderFactory,
        AmountOfMoneyFactory $amountOfMoneyFactory,
        OrderReferencesFactory $orderReferencesFactory,
        AdditionalInputBuilder $additionalInputBuilder,
        ShippingBuilder $shippingBuilder,
        MerchantReference $merchantReference,
        Format $format
    ) {
        $this->ePaymentsConfig = $ePaymentsConfig;
        $this->customerBuilder = $customerBuilder;
        $this->shoppingCartBuilder = $shoppingCartBuilder;
        $this->orderFactory = $orderFactory;
        $this->amountOfMoneyFactory = $amountOfMoneyFactory;
        $this->orderReferencesFactory = $orderReferencesFactory;
        $this->merchantReference = $merchantReference;
        $this->shippingBuilder = $shippingBuilder;
        $this->additionalInputBuilder = $additionalInputBuilder;
        $this->format = $format;
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
        $ingenicoOrder->additionalInput = $this->additionalInputBuilder->create($order);
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
        $references->merchantReference = $this->format->limit(
            $this->merchantReference->generateMerchantReferenceForOrder($order),
            30
        );
        $references->descriptor = $this->ePaymentsConfig->getDescriptor();

        return $references;
    }
}
