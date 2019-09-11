<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\Common\Order;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\ShoppingCartBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ShoppingCart;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Catalog\Model\Product;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\Item\Collection as OrderItemCollection;

class ShoppingCartBuilderTest extends AbstractTestCase
{
    /**
     * @var ShoppingCartBuilder
     */
    private $subject;

    /**
     * @var OrderItemCollectionFactory|MockObject
     */
    private $mockedOrderItemCollectionFactory;

    protected function setUp()
    {
        $this->mockedOrderItemCollectionFactory = $this->getMockBuilder(OrderItemCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subject = $this->getObjectManager()->getObject(
            ShoppingCartBuilder::class,
            [
                'shoppingCartFactory' => $this->getMockForFactory(ShoppingCart::class),
                'orderItemCollectionFactory' => $this->mockedOrderItemCollectionFactory,
            ]
        );
    }

    public function testPropertyWillNotBePopulateByGuestCustomer()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(1);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertNull($result->reOrderIndicator);
    }

    public function testRegisteredCustomerWithNoPreviousOrderWillPopulatePropertyWithTrue()
    {
        /// Setup:
        $mockedProduct = $this->prepareMockedProduct('sku1');
        $mockedItem = $this->prepareMockedItem($mockedProduct);
        $mockedOrder = $this->prepareMockedOrder($mockedItem);
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);

        $mockedCollection = $this->getMockBuilder(OrderItemCollection::class)->disableOriginalConstructor()->getMock();
        $mockedCollection->method('join')->willReturn($mockedCollection);
        $mockedCollection->method('addFieldToFilter')->willReturn($mockedCollection);
        $mockedCollection->method('getSize')->willReturn(2);

        $this->mockedOrderItemCollectionFactory->method('create')->willReturn($mockedCollection);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertTrue($result->reOrderIndicator);
    }

    /**
     * @param $mockedItem
     * @return Order|MockObject
     */
    private function prepareMockedOrder($mockedItem)
    {
        /** @var Order|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);
        $mockedOrder->method('getAllVisibleItems')->willReturn([$mockedItem]);
        return $mockedOrder;
    }

    /**
     * @return Product|MockObject
     */
    private function prepareMockedProduct(string $sku)
    {
        $mockedProduct1 = $this->getMockBuilder(Product::class)->disableOriginalConstructor()->getMock();
        $mockedProduct1->method('getSku')->willReturn($sku);
        return $mockedProduct1;
    }

    /**
     * @param $mockedProduct
     * @return Item|MockObject
     */
    private function prepareMockedItem($mockedProduct)
    {
        $mockedItem = $this->getMockBuilder(Item::class)->disableOriginalConstructor()->getMock();
        $mockedItem->method('getProduct')->willReturn($mockedProduct);
        return $mockedItem;
    }
}
