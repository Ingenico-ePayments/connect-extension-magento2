<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecure;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\ConfigInterface;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecure\RedirectionDataBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\RedirectionData;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;

class RedirectionDataBuilderTest extends AbstractTestCase
{
    /**
     * @var RedirectionDataBuilder
     */
    private $subject;

    /**
     * @var ConfigInterface|MockObject
     */
    private $mockedConfigInterface;

    /**
     * @var UrlInterface|MockObject
     */
    private $mockedUrlBuilder;

    /**
     * @var OrderPaymentInterface|MockObject
     */
    private $mockedOrderPaymentInterface;

    protected function setUp()
    {
        $this->mockedConfigInterface = $this->getMockBuilder(ConfigInterface::class)->getMock();
        $this->mockedUrlBuilder = $this->getMockBuilder(UrlInterface::class)->getMock();
        $this->mockedOrderPaymentInterface = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();

        $this->subject = $this->getObjectManager()->getObject(
            RedirectionDataBuilder::class,
            [
                'redirectionDataFactory' => $this->getMockForFactory(RedirectionData::class),
                'config' => $this->mockedConfigInterface,
                'urlBuilder' => $this->mockedUrlBuilder,
            ]
        );
    }

    public function testMissingPropertiesWillSetNullValues()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();

        // Set expectations:
        $this->mockedUrlBuilder->expects(self::never())->method('getUrl');

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertNull($result->returnUrl);
        self::assertNull($result->variant);
    }

    public function testCheckoutVariant()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $mockedOrder->method('getStoreId')->willReturn(1);
        $this->mockedConfigInterface->method('getHostedCheckoutVariant')->willReturn('100');

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertEquals('100', $result->variant);
    }

    public function testRedirectPaymentWillReturnUrl()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $this->mockedOrderPaymentInterface
            ->method('getAdditionalInformation')
            ->willReturn([Config::CLIENT_PAYLOAD_KEY => 1]);
        $mockedOrder->method('getPayment')->willReturn($this->mockedOrderPaymentInterface);
        $this->mockedUrlBuilder->method('getUrl')->willReturn('https://www.example.com');

        // Set expectations:
        $this->mockedUrlBuilder
            ->expects(self::once())
            ->method('getUrl')
            ->with(RequestBuilder::REDIRECT_PAYMENT_RETURN_URL);

        // Exercise:
        $this->subject->create($mockedOrder);
    }

    public function testHostedCheckoutWillReturnUrl()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $this->mockedOrderPaymentInterface
            ->method('getAdditionalInformation')
            ->willReturn([]);
        $mockedOrder->method('getPayment')->willReturn($this->mockedOrderPaymentInterface);
        $this->mockedUrlBuilder->method('getUrl')->willReturn('https://www.example.com');

        // Set expectations:
        $this->mockedUrlBuilder
            ->expects(self::once())
            ->method('getUrl')
            ->with(RequestBuilder::HOSTED_CHECKOUT_RETURN_URL);

        // Exercise:
        $this->subject->create($mockedOrder);
    }
}
