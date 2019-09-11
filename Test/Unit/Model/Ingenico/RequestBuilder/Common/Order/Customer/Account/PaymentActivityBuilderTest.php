<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\Common\Order\Customer\Account;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\Account\PaymentActivityBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerPaymentActivity;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Payment\Collection as PaymentCollection;
use Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory as PaymentCollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;

class PaymentActivityBuilderTest extends AbstractTestCase
{
    /**
     * @var PaymentActivityBuilder
     */
    private $subject;

    /**
     * @var PaymentCollectionFactory|MockObject
     */
    private $mockedPaymentCollectionFactory;

    /**
     * @var MockObject|SearchCriteriaBuilder
     */
    private $mockedSearchCriteriaBuilder;

    /**
     * @var MockObject|OrderRepositoryInterface
     */
    private $mockedOrderRepository;

    protected function setUp()
    {
        $this->mockedPaymentCollectionFactory = $this->getMockBuilder(PaymentCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockedOrderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)->getMock();
        $this->mockedSearchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockedSearchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $this->mockedSearchCriteriaBuilder->method('create')->willReturn(
            $this->getMockBuilder(SearchCriteriaInterface::class)->getMock()
        );

        $this->subject = $this->getObjectManager()->getObject(
            PaymentActivityBuilder::class,
            [
                'paymentActivityFactory' => $this->getMockForFactory(CustomerPaymentActivity::class),
                'paymentCollectionFactory' => $this->mockedPaymentCollectionFactory,
                'dateTimeFactory' => new DateTimeFactory(),
                'searchCriteriaBuilder' => $this->mockedSearchCriteriaBuilder,
                'orderRepository' => $this->mockedOrderRepository,
            ]
        );
    }

    public function testGuestCustomerWillNotPopulateProperties()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(true);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertInstanceOf(CustomerPaymentActivity::class, $result);
        self::assertNull($result->numberOfPaymentAttemptsLast24Hours);
        self::assertNull($result->numberOfPaymentAttemptsLastYear);
        self::assertNull($result->numberOfPurchasesLast6Months);
    }

    public function testGuestCustomerWillPopulateProperties()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);
        $mockedCollection = $this->getMockBuilder(PaymentCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedCollection->method('join')->willReturnSelf();
        $mockedCollection->method('addFieldToFilter')->willReturnSelf();
        $mockedCollection->method('getSize')->willReturn(6);
        /** @var MockObject|OrderSearchResultInterface $mockedSearchResults */
        $mockedSearchResults = $this->getMockBuilder(OrderSearchResultInterface::class)->getMock();
        $mockedSearchResults->method('getTotalCount')->willReturn(2);
        $this->mockedOrderRepository->method('getList')->willReturn($mockedSearchResults);

        $this->mockedPaymentCollectionFactory->method('create')->willReturn($mockedCollection);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertEquals(2, $result->numberOfPurchasesLast6Months);
        self::assertEquals(6, $result->numberOfPaymentAttemptsLast24Hours);
        self::assertEquals(6, $result->numberOfPaymentAttemptsLastYear);
    }
}
