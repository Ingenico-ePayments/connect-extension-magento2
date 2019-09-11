<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\Common\Order\Customer;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\AccountBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerAccount;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\ResourceModel\Address\Collection;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory as CustomerAddressCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

class AccountBuilderTest extends AbstractTestCase
{
    /**
     * @var AccountBuilder
     */
    private $subject;

    /**
     * @var CustomerRepositoryInterface|MockObject
     */
    private $mockedCustomerRepository;

    /**
     * @var MockObject|OrderRepositoryInterface
     */
    private $mockedOrderRepository;

    /**
     * @var OrderSearchResultInterface|MockObject
     */
    private $mockedSearchResults;

    /**
     * @var CustomerAddressCollectionFactory|MockObject
     */
    private $mockedCustomerAddressCollectionFactory;

    protected function setUp()
    {
        $this->mockedCustomerRepository = $this->getMockBuilder(CustomerRepositoryInterface::class)->getMock();
        $this->mockedOrderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)->getMock();
        $mockedSearchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedSearchCriteriaBuilder->method('addFilter')->willReturnSelf();
        $mockedSearchCriteriaBuilder->method('create')->willReturn(
            $this->getMockBuilder(SearchCriteriaInterface::class)->getMock()
        );
        $this->mockedSearchResults = $this->getMockBuilder(OrderSearchResultInterface::class)->getMock();
        $this->mockedOrderRepository->method('getList')->willReturn($this->mockedSearchResults);
        $this->mockedCustomerAddressCollectionFactory = $this
            ->getMockBuilder(CustomerAddressCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getObjectManager()->getObject(
            AccountBuilder::class,
            [
                'customerAccountFactory' => $this->getMockForFactory(CustomerAccount::class),
                'customerRepository' => $this->mockedCustomerRepository,
                'dateTimeFactory' => new DateTimeFactory(),
                'orderRepository' => $this->mockedOrderRepository,
                'searchCriteriaBuilder' => $mockedSearchCriteriaBuilder,
                'customerAddressCollectionFactory' => $this->mockedCustomerAddressCollectionFactory,
            ]
        );
    }

    public function testGuestCustomerWillNotPopulateDateProperties()
    {
        // Setup:
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(true);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertNull($result->createDate);
        self::assertNull($result->changeDate);
        self::assertNull($result->hadSuspiciousActivity);
    }

    public function testLoggedInCustomerWillPopulateProperties()
    {
        // Setup:
        $mockedOrder = $this->mockOrder();
        /** @var CustomerInterface|MockObject $mockedCustomer */
        $mockedCustomer = $this->getMockBuilder(CustomerInterface::class)->getMock();
        $mockedCustomer->method('getId')->willReturn(1);
        $mockedCustomer->method('getCreatedAt')->willReturn('1984-05-25 14:15:00');
        $mockedCustomer->method('getUpdatedAt')->willReturn('1984-05-26 14:15:00');
        $this->mockedCustomerRepository->method('getById')->willReturn($mockedCustomer);
        $this->prepareMockedCustomerAddressCollection('2015-06-25 14:15:00');

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertSame('19840525', $result->createDate);
        self::assertSame('20150625', $result->changeDate);
    }

    public function testLoggedInCustomerWillPopulateChangeDateOnCustomer()
    {
        // Setup:
        $mockedOrder = $this->mockOrder();
        /** @var CustomerInterface|MockObject $mockedCustomer */
        $mockedCustomer = $this->getMockBuilder(CustomerInterface::class)->getMock();
        $mockedCustomer->method('getId')->willReturn(1);
        $mockedCustomer->method('getCreatedAt')->willReturn('1984-05-25 14:15:00');
        $mockedCustomer->method('getUpdatedAt')->willReturn('1984-05-26 14:15:00');
        $this->mockedCustomerRepository->method('getById')->willReturn($mockedCustomer);
        $this->prepareMockedCustomerAddressCollection('1984-05-26 14:15:00');

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertSame('19840526', $result->changeDate);
    }

    public function testLoggedInCustomerWithFraudulentOrdersAreMarkedAsSuspicious()
    {
        // Setup:
        $mockedOrder = $this->mockOrder();
        /** @var CustomerInterface|MockObject $mockedCustomer */
        $mockedCustomer = $this->getMockBuilder(CustomerInterface::class)->getMock();
        $mockedCustomer->method('getId')->willReturn(1);
        $this->mockedCustomerRepository->method('getById')->willReturn($mockedCustomer);
        $this->mockedSearchResults->method('getTotalCount')->willReturn(1);

        $this->prepareMockedCustomerAddressCollection(null);
        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertTrue($result->hadSuspiciousActivity);
    }

    public function testLoggedInCustomerWithoutFraudulentOrdersAreNotMarkedAsSuspicious()
    {
        // Setup:
        $mockedOrder = $this->mockOrder();
        /** @var CustomerInterface|MockObject $mockedCustomer */
        $mockedCustomer = $this->getMockBuilder(CustomerInterface::class)->getMock();
        $mockedCustomer->method('getId')->willReturn(1);
        $this->mockedCustomerRepository->method('getById')->willReturn($mockedCustomer);
        $this->mockedSearchResults->method('getTotalCount')->willReturn(0);

        $this->prepareMockedCustomerAddressCollection('2015-06-25 14:15:00');
        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertFalse($result->hadSuspiciousActivity);
    }

    /**
     * @return OrderInterface|MockObject
     */
    private function mockOrder()
    {
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);
        return $mockedOrder;
    }

    private function prepareMockedCustomerAddressCollection($updatedAt)
    {
        $mockedAddressCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedAddress = $this->getMockBuilder(Address::class)->disableOriginalConstructor()->getMock();
        if ($updatedAt !== null) {
            $mockedAddress->method('getEntityId')->willReturn(1);
        }
        $mockedAddress->method('getData')->with('updated_at', null)->willReturn($updatedAt);
        $mockedAddressCollection->method('addFieldToFilter')->willReturnSelf();
        $mockedAddressCollection->method('addAttributeToSort')->willReturnSelf();
        $mockedAddressCollection->method('setPageSize')->willReturnSelf();
        $mockedAddressCollection->method('getFirstItem')->willReturn($mockedAddress);

        $this->mockedCustomerAddressCollectionFactory->method('create')->willReturn($mockedAddressCollection);
    }
}
