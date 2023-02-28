<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ShoppingCart;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ShoppingCartFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\ShoppingCart\ItemsBuilder;

/**
 * Class ShoppingCartBuilder
 */
class ShoppingCartBuilder
{
    /**
     * @var ShoppingCartFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $shoppingCartFactory;

    /**
     * @var ItemsBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $itemsBuilder;

    /**
     * @var OrderItemCollectionFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $orderItemCollectionFactory;

    public function __construct(
        ShoppingCartFactory $shoppingCartFactory,
        ItemsBuilder $itemsBuilder,
        OrderItemCollectionFactory $orderItemCollectionFactory
    ) {
        $this->shoppingCartFactory = $shoppingCartFactory;
        $this->itemsBuilder = $itemsBuilder;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
    }

    public function create(OrderInterface $order): ShoppingCart
    {
        $shoppingCart = $this->shoppingCartFactory->create();

        if ($order instanceof Order) {
            $shoppingCart->items = $this->itemsBuilder->create($order);
        }

        try {
            $shoppingCart->reOrderIndicator = $this->getIsReOrder($order);
        // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        } catch (LocalizedException $exception) {
            //Do nothing
        }

        return $shoppingCart;
    }

    /**
     * @throws LocalizedException
     */
    private function getIsReOrder(OrderInterface $order): bool
    {
        if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Cannot get previous orders'));
        }
        $itemCollection = $this->orderItemCollectionFactory->create();
        $itemCollection
            ->join(
                ['o' => 'sales_order'],
                'main_table.order_id = o.entity_id',
                ['sku' => 'main_table.sku']
            )
            ->addFieldToFilter('o.customer_id', $order->getCustomerId())
            ->addFieldToFilter('o.total_due', ['lteq' => 0.01])
            ->addFieldToFilter('o.entity_id', ['neq' => $order->getEntityId()])
            ->addFieldToFilter('main_table.sku', ['in' => $this->getVisibleSkusFromOrder($order)]);

        return !($itemCollection->getSize() === 0);
    }

    // phpcs:disable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    /**
     * @param OrderInterface $order
     * @return string[]
     * @throws LocalizedException
     */
    // phpcs:enable SlevomatCodingStandard.TypeHints.DisallowArrayTypeHintSyntax.DisallowedArrayTypeHintSyntax
    private function getVisibleSkusFromOrder(OrderInterface $order): array
    {
        if (!$order instanceof Order) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Cannot get all visible items for OrderInterface'));
        }
        $visibleSkus = [];
        /** @var Item $orderItem */
        foreach ($order->getAllVisibleItems() as $orderItem) {
            $visibleSkus[] = $orderItem->getSku();
        }
        return $visibleSkus;
    }
}
