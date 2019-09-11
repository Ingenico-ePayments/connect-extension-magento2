<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\Common\Order;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\CustomerBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Customer;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalInformation;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalName;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;

class CustomerBuilderTest extends AbstractTestCase
{
    /**
     * @var CustomerBuilder
     */
    private $subject;

    protected function setUp()
    {
        $this->subject = $this->getObjectManager()->getObject(
            CustomerBuilder::class,
            [
                'customerFactory' => $this->getMockForFactory(Customer::class),
                'personalNameFactory' => $this->getMockForFactory(PersonalName::class),
                'personalInformationFactory' => $this->getMockForFactory(PersonalInformation::class),
            ]
        );
    }

    public function testGuestCustomerIsMarkedAsGuest()
    {
        // Setup:
        /** @var Order|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(1);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertEquals(
            CustomerBuilder::ACCOUNT_TYPE_NONE,
            $result->accountType
        );
    }

    public function testRegisteredCustomerIsMarkedAsExisting()
    {
        // Setup:
        /** @var Order|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(0);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertEquals(
            CustomerBuilder::ACCOUNT_TYPE_EXISTING,
            $result->accountType
        );
    }
}
