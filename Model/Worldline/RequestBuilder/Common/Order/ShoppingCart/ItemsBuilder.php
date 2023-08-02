<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\ShoppingCart;

use Ingenico\Connect\Sdk\Domain\Definitions\AmountOfMoneyFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\LineItem;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\LineItemFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\LineItemInvoiceDataFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\OrderLineDetailsFactory;
use Magento\Sales\Model\Order;
use Worldline\Connect\Helper\Data as DataHelper;

class ItemsBuilder
{
    /**
     * @var LineItemFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $lineItemFactory;

    /**
     * @var AmountOfMoneyFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $amountOfMoneyFactory;

    /**
     * @var LineItemInvoiceDataFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $lineItemInvoiceDataFactory;

    /**
     * @var OrderLineDetailsFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    public function create(Order $order)
    {
        $lineItems = [];
        // phpcs:ignore SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
        /** @var Order\Item[] $orderItems */
        $orderItems = $order->getAllVisibleItems();

        foreach ($orderItems as $item) {
            if ($item->getParentItem()) {
                /** Only add base items. */
                continue;
            }

            $lineItem = $this->lineItemFactory->create();

            $itemAmountOfMoney = $this->amountOfMoneyFactory->create();
            $itemAmountOfMoney->amount = DataHelper::formatWorldlineAmount($item->getRowTotalInclTax());
            $itemAmountOfMoney->currencyCode = $order->getOrderCurrencyCode();
            $lineItem->amountOfMoney = $itemAmountOfMoney;

            $lineItemInvoiceData = $this->lineItemInvoiceDataFactory->create();
            $lineItemInvoiceData->nrOfItems = (int) $item->getQtyOrdered();
            $lineItemInvoiceData->description = $item->getName();
            $lineItemInvoiceData->pricePerItem = DataHelper::formatWorldlineAmount($item->getPriceInclTax());
            $lineItem->invoiceData = $lineItemInvoiceData;

            $orderLineDetails = $this->orderLineDetailsFactory->create();
            $orderLineDetails->discountAmount = DataHelper::formatWorldlineAmount($item->getDiscountAmount());
            $orderLineDetails->lineAmountTotal = DataHelper::formatWorldlineAmount($item->getRowTotalInclTax());
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $orderLineDetails->productCode = substr($item->getSku(), 0, 12);
            $orderLineDetails->productPrice = DataHelper::formatWorldlineAmount($item->getPriceInclTax());
            $orderLineDetails->productType = $item->getProductType();
            $orderLineDetails->quantity = (int) $item->getQtyOrdered();
            $orderLineDetails->productName = $item->getProduct()->getName();
            $taxAmount = $item->getTaxBeforeDiscount()
                ?: $item->getTaxAmount() + $item->getDiscountTaxCompensationAmount();
            $orderLineDetails->taxAmount = DataHelper::formatWorldlineAmount($taxAmount);
            $orderLineDetails->unit = '';
            $lineItem->orderLineDetails = $orderLineDetails;

            $lineItems[] = $lineItem;
        }
        /**
         * Add shipping amount as fake line item
         */
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
        if ($order->getShippingAmount() != 0) {
            $lineItems[] = $this->getShippingItem($order);
        }

        return $lineItems;
    }

    /**
     * @param Order $order
     * @return LineItem
     */
    private function getShippingItem(Order $order)
    {
        $formatAmountShip = DataHelper::formatWorldlineAmount($order->getShippingInclTax());
        $shippingAmount = $this->amountOfMoneyFactory->create();
        $shippingAmount->amount = $formatAmountShip;
        $shippingAmount->currencyCode = $order->getOrderCurrencyCode();

        $shippingDetails = $this->orderLineDetailsFactory->create();
        $shippingDetails->productCode = 'shipping';
        $shippingDetails->productName = 'Shipping';
        $shippingDetails->quantity = 1;
        $shippingDetails->taxAmount = DataHelper::formatWorldlineAmount($order->getShippingTaxAmount());
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
        $formatAmountDisc = DataHelper::formatWorldlineAmount($order->getDiscountAmount());
        $discountAmount = $this->amountOfMoneyFactory->create();
        $discountAmount->amount = $formatAmountDisc;
        $discountAmount->currencyCode = $order->getOrderCurrencyCode();

        $discountDetails = $this->orderLineDetailsFactory->create();
        $discountDetails->productCode = 'discount';
        $discountDetails->productName = 'Discount';
        $discountDetails->quantity = 1;
        $discountDetails->lineAmountTotal = $formatAmountDisc;
        $discountDetails->productPrice = $formatAmountDisc;
        $discountDetails->taxAmount = DataHelper::formatWorldlineAmount(
            -$order->getDiscountTaxCompensationAmount()
        );
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
