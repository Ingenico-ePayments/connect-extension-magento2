<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\CreateHostedCheckout;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder as CommonRequestBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\CreateHostedCheckout\RequestBuilder;
use Ingenico\Connect\Model\Ingenico\Token\TokenServiceInterface;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\Definitions\HostedCheckoutSpecificInput;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use PHPUnit\Framework\MockObject\MockObject;

class RequestBuilderTest extends AbstractTestCase
{
    /**
     * @var RequestBuilder
     */
    private $subject;

    /**
     * @var TokenServiceInterface|MockObject
     */
    private $mockedTokenService;

    protected function setUp()
    {
        $mockedCommonRequestBuilder = $this->getMockBuilder(CommonRequestBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedCommonRequestBuilder->method('create')->willReturnArgument(0);
        $this->mockedTokenService = $this->getMockBuilder(TokenServiceInterface::class)->getMock();

        $this->subject = $this->getObjectManager()->getObject(
            RequestBuilder::class,
            [
                'createHostedCheckoutRequestFactory' =>
                    $this->getMockForFactory(CreateHostedCheckoutRequest::class),
                'hostedCheckoutSpecificInputFactory' =>
                    $this->getMockForFactory(HostedCheckoutSpecificInput::class),
                'requestBuilder' => $mockedCommonRequestBuilder,
                'tokenService' => $this->mockedTokenService,
            ]
        );
    }

    public function testTokensIsNullForGuestCustomers()
    {
        // Setup:
        /** @var Order|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(true);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertNull($result->hostedCheckoutSpecificInput->tokens);
    }

    public function testTokensIsNullForCustomersWithoutTokens()
    {
        // Setup:
        /** @var Order|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->disableOriginalConstructor()->getMock();
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $this->mockedTokenService->method('find')->willReturn([]);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertNull($result->hostedCheckoutSpecificInput->tokens);
    }

    public function testTokensArePopulatedForCustomersWithTokens()
    {
        // Setup:
        /** @var Order|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(Order::class)->disableOriginalConstructor()->getMock();
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->disableOriginalConstructor()->getMock();
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $this->mockedTokenService->method('find')->willReturn(['foo-123', 'bar-456']);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertEquals('foo-123,bar-456', $result->hostedCheckoutSpecificInput->tokens);
    }
}
