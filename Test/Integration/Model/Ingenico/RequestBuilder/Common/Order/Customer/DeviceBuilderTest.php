<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\Common\Order\Customer;

use Exception;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;
use Magento\TestFramework\Request;
use PHPUnit\Framework\MockObject\MockObject;

class DeviceBuilderTest extends AbstractRequestBuilderTestCase
{
    const FAKE_REQUEST_HEADER = 'Fake Request Header';

    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    protected function setUp()
    {
        parent::setUp();
        /** @var Request|MockObject $mockedRequestHeader */
        $mockedRequestHeader = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->getMock();
        $mockedRequestHeader
            ->method('getHeader')
            ->with('Accept', false)
            ->willReturn(self::FAKE_REQUEST_HEADER);
        $this->objectManager->addSharedInstance($mockedRequestHeader, Request::class);
        $this->requestBuilder = $this->objectManager->get(RequestBuilder::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testCreateRequestForGuestCustomer()
    {
        // Setup:
        $order = $this->orderFixture->createOrder();

        // Exercise:
        $request = $this->requestBuilder->create(new CreatePaymentRequest(), $order);

        // Verify:
        self::assertEquals(self::FAKE_REQUEST_HEADER, $request->order->customer->device->acceptHeader);
    }
}
