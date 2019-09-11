<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\MerchantBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Merchant;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Module\Manager;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class MerchantBuilderTest extends AbstractTestCase
{
    /**
     * @var MerchantBuilder
     */
    private $subject;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $mockedConfig;

    /**
     * @var Manager|MockObject
     */
    private $mockedModuleManager;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $mockedStoreManager;

    /**
     * @var Store|MockObject
     */
    private $mockedStore;

    protected function setUp()
    {
        $this->mockedConfig = $this->getMockBuilder(ScopeConfigInterface::class)->getMock();
        $this->mockedModuleManager = $this->getMockBuilder(Manager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->mockedStoreManager = $this->getMockBuilder(StoreManagerInterface::class)->getMock();
        $this->mockedStore = $this->getMockBuilder(Store::class)->disableOriginalConstructor()->getMock();
        $this->mockedStoreManager->method('getStore')->willReturn($this->mockedStore);

        $this->subject = $this->getObjectManager()->getObject(
            MerchantBuilder::class,
            [
                'merchantFactory' => $this->getMockForFactory(Merchant::class),
                'storeManager' => $this->mockedStoreManager,
                'config' => $this->mockedConfig,
                'moduleManager' => $this->mockedModuleManager,
            ]
        );
    }

    public function testMerchantWebsiteUrl()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();

        // Set expectations:
        $this->mockedStore
            ->expects(self::once())
            ->method('getBaseUrl')
            ->willReturn('https://www.example.com');

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertInstanceOf(Merchant::class, $result);
        self::assertEquals('https://www.example.com', $result->websiteUrl);
    }

    public function testContactWebsiteUrl()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $this->mockedConfig->method('getValue')->willReturn('1');
        $this->mockedModuleManager->method('isEnabled')->willReturn(true);

        // Set expectations:
        $this->mockedStore
            ->expects(self::once())
            ->method('getUrl')
            ->with('contact')
            ->willReturn('https://www.example.com/contact');

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertEquals('https://www.example.com/contact', $result->contactWebsiteUrl);
    }

    public function testPropertyWillBeNullIfModuleIsDisabled()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $this->mockedConfig->method('getValue')->willReturn('0');
        $this->mockedModuleManager->method('isEnabled')->willReturn(true);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertNull($result->contactWebsiteUrl);
    }

    public function testPropertyWillBeNullIfModuleIsNotInstalled()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        $this->mockedConfig->method('getValue')->willReturn('1');
        $this->mockedModuleManager->method('isEnabled')->willReturn(false);

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertNull($result->contactWebsiteUrl);
    }
}
