<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\Common\Order\Customer\Account;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\Account\AuthenticationBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerAccountAuthentication;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Customer\Model\Log;
use Magento\Customer\Model\Logger;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;

class AuthenticationBuilderTest extends AbstractTestCase
{
    /**
     * @var AuthenticationBuilder
     */
    private $subject;

    /**
     * @var Logger|MockObject
     */
    private $mockedCustomerLogger;

    protected function setUp()
    {
        $this->mockedCustomerLogger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject = $this->getObjectManager()->getObject(
            AuthenticationBuilder::class,
            [
                'authenticationFactory' => $this->getMockForFactory(CustomerAccountAuthentication::class),
                'customerLogger' => $this->mockedCustomerLogger,
                'dateTimeFactory' => new DateTimeFactory(),
            ]
        );
    }

    public function testGuestCustomerIsMarkedAsGuest()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(true);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertSame(
            AuthenticationBuilder::GUEST,
            $result->method
        );
    }

    public function testLoggedInCustomerIsMarkedAsMerchantCredentials()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertSame(
            AuthenticationBuilder::MERCHANT_CREDENTIALS,
            $result->method
        );
    }

    public function testGuestCustomerWillNotPopulateLastLoginAt()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(true);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertNull($result->utcTimestamp);
    }

    public function testLoggedInCustomerWillPopulateLastLoginAt()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);

        /** @var Log|MockObject $mockedLog */
        $mockedLog = $this->getMockBuilder(Log::class)->disableOriginalConstructor()->getMock();
        $mockedLog->method('getLastLoginAt')->willReturn('2019-07-15 07:43:06');
        $this->mockedCustomerLogger->method('get')->willReturn($mockedLog);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertSame('201907150743', $result->utcTimestamp);
    }
}
