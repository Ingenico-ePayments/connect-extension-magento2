<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\Common\Order\Customer;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\DeviceBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\BrowserData;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerDevice;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Framework\App\Request\Http;
use PHPUnit\Framework\MockObject\MockObject;

class DeviceBuilderTest extends AbstractTestCase
{
    /**
     * @var DeviceBuilder
     */
    private $subject;

    /**
     * @var Http|MockObject
     */
    private $mockedHttpRequest;

    protected function setUp()
    {
        parent::setUp();

        $this->mockedHttpRequest = $this->getMockBuilder(Http::class)->disableOriginalConstructor()->getMock();
        $this->subject = $this->getObjectManager()->getObject(
            DeviceBuilder::class,
            [
                'customerDeviceFactory' => $this->getMockForFactory(CustomerDevice::class),
                'browserDataFactory' => $this->getMockForFactory(BrowserData::class),
                'request' => $this->mockedHttpRequest,
            ]
        );
    }

    public function testIfAcceptHeaderIsPresent()
    {
        // Setup:
        $acceptHeaderExpected = 'application/json, text/javascript, */*; q=0.01';
        $this->mockedHttpRequest->method('getHeader')->willReturn($acceptHeaderExpected);

        // Exercise:
        $result = $this->subject->create();

        // Verify:
        self::assertEquals($acceptHeaderExpected, $result->acceptHeader);
    }

    public function testAcceptHeaderIsNotPresent()
    {
        // Exercise:
        $result = $this->subject->create();

        // Verify:
        self::assertNull($result->acceptHeader);
    }
}
