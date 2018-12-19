<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Sdk\Domain\Definitions\AmountOfMoneyFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\LineItem;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\LineItemFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\LineItemInvoiceDataFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderLineDetailsFactory;
use Magento\Sales\Model\Order;
use Netresearch\Epayments\Helper\Data as DataHelper;

/**
 * Class LineItemsBuilder
 */
class LineItemsBuilder
{
    /**
     * @var LineItemFactory
     */
    private $lineItemFactory;

    /**
     * @var AmountOfMoneyFactory
     */
    private $amountOfMoneyFactory;

    /**
     * @var LineItemInvoiceDataFactory
     */
    private $lineItemInvoiceDataFactory;

    /**
     * @var OrderLineDetailsFactory
     */
    private $orderLineDetailsFactory;

    /**
     * LineItemsBuilder constructor.
     *
     * @param LineItemFactory $lineItemFactory
     * @param AmountOfMoneyFactory $amountOfMoneyFactory
     * @param LineItemInvoiceDataFactory $lineItemInvoiceDataFactory
     * @param OrderLineDetailsFactory $orderLineDetailsFactory
     */
    public function __construct(
        LineItemFactory $lineItemFactory,
        AmountOfMoneyFactory $amountOfMoneyFactory,
        LineItemInvoiceDataFactory $lineItemInvoiceDataFactory,
        OrderLineDetailsFactory $orderLineDetailsFactory
    ) {
        $this->lineItemFactory = $lineItemFactory;
        $this->amountOfMoneyFactory = $amountOfMoneyFactory;
        $this->lineItemInvoiceDataFactory = $lineItemInvoiceDataFactory;
        $this->orderLineDetailsFactory = $orderLineDetailsFactory;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function create(Order $order)
    {
        $lineItems = [];
        /** @var Order\Item[] $orderItems */
        $orderItems = $order->getAllVisibleItems();

        foreach ($orderItems as $item) {
            if ($item->getParentItem()) {
                /** Only add base items. */
                continue;
            }

            $lineItem = $this->lineItemFactory->create();

            $itemAmountOfMoney = $this->amountOfMoneyFactory->create();
            $itemAmountOfMoney->amount = DataHelper::formatIngenicoAmount($item->getBaseRowTotalInclTax());
            $itemAmountOfMoney->currencyCode = $order->getBaseCurrencyCode();
            $lineItem->amountOfMoney = $itemAmountOfMoney;

            $lineItemInvoiceData = $this->lineItemInvoiceDataFactory->create();
            $lineItemInvoiceData->nrOfItems = $item->getQtyOrdered();
            $lineItemInvoiceData->description = $item->getName();
            $lineItemInvoiceData->pricePerItem = DataHelper::formatIngenicoAmount($item->getBasePriceInclTax());
            $lineItem->invoiceData = $lineItemInvoiceData;

            $orderLineDetails = $this->orderLineDetailsFactory->create();
            $orderLineDetails->discountAmount = DataHelper::formatIngenicoAmount($item->getBaseDiscountAmount());
            $orderLineDetails->lineAmountTotal = DataHelper::formatIngenicoAmount($item->getBaseRowTotalInclTax());
            $orderLineDetails->productCode = substr($item->getSku(), 0, 12);
            $orderLineDetails->productPrice = DataHelper::formatIngenicoAmount($item->getBasePriceInclTax());
            $orderLineDetails->productType = $item->getProductType();
            $orderLineDetails->quantity = $item->getQtyOrdered();
            $orderLineDetails->productName = $item->getProduct()->getName();
            $taxAmount = $item->getBaseTaxBeforeDiscount()
                ?: $item->getBaseTaxAmount() + $item->getBaseDiscountTaxCompensationAmount();
            $orderLineDetails->taxAmount = DataHelper::formatIngenicoAmount($taxAmount);
            $orderLineDetails->unit = '';
            $lineItem->orderLineDetails = $orderLineDetails;

            $lineItems[] = $lineItem;
        }
        /**
         * Add shipping amount as fake line item
         */
        if ($order->getBaseShippingAmount() != 0) {
            $lineItems[] = $this->getShippingItem($order);
        }
        /**
         * Add discounts as fake line item
         */
        if ($order->getBaseDiscountAmount() != 0) {
            $lineItems[] = $this->getDiscountsItem($order);
        }

        return $lineItems;
    }

    /**
     * @param Order $order
     * @return LineItem
     */
    private function getShippingItem(Order $order)
    {
        $formatAmountShip = DataHelper::formatIngenicoAmount($order->getBaseShippingInclTax());
        $shippingAmount = $this->amountOfMoneyFactory->create();
        $shippingAmount->amount = $formatAmountShip;
        $shippingAmount->currencyCode = $order->getBaseCurrencyCode();

        $shippingDetails = $this->orderLineDetailsFactory->create();
        $shippingDetails->productName = 'Shipping';
        $shippingDetails->quantity = 1;
        $shippingDetails->taxAmount = DataHelper::formatIngenicoAmount($order->getBaseShippingTaxAmount());
        $shippingDetails->lineAmountTotal = $formatAmountShip;
        $shippingDetails->productPrice = $formatAmountShip;

        $shippingInvoice = $this->lineItemInvoiceDataFactory->create();
        $shippingInvoice->description = 'Shipping';
        $shippingInvoice->nrOfItems = 1;
        $shippingInvoice->pricePerItem = $formatAmountShip;

        $shippingItem = $this->lineItemFactory->create();
        $shippingItem->amountOfMoney = $shippingAmount;
        $shippingItem->orderLineDetails = $shippingDetails;
        $shippingItem->invoiceData = $shippingInvoice;

        return $shippingItem;
    }

    /**
     * @param Order $order
     * @return LineItem
     */
    private function getDiscountsItem(Order $order)
    {
        $formatAmountDisc = DataHelper::formatIngenicoAmount($order->getBaseDiscountAmount());
        $discountAmount = $this->amountOfMoneyFactory->create();
        $discountAmount->amount = $formatAmountDisc;
        $discountAmount->currencyCode = $order->getBaseCurrencyCode();

        $discountDetails = $this->orderLineDetailsFactory->create();
        $discountDetails->productName = 'Discount';
        $discountDetails->quantity = 1;
        $discountDetails->lineAmountTotal = $formatAmountDisc;
        $discountDetails->productPrice = $formatAmountDisc;
        $discountDetails->taxAmount = DataHelper::formatIngenicoAmount(-$order->getBaseDiscountTaxCompensationAmount());
        $discountInvoice = $this->lineItemInvoiceDataFactory->create();
        $description = $order->getDiscountDescription() ?: 'Discount';
        $discountInvoice->description = $description;
        $discountInvoice->nrOfItems = 1;
        $discountInvoice->pricePerItem = $formatAmountDisc;

        $discountItem = $this->lineItemFactory->create();
        $discountItem->amountOfMoney = $discountAmount;
        $discountItem->orderLineDetails = $discountDetails;
        $discountItem->invoiceData = $discountInvoice;

        return $discountItem;
    }
}
