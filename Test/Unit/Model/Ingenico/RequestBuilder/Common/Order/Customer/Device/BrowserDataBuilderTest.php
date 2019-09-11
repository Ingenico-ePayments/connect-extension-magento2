<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\Common\Order\Customer\Device;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\Device\BrowserDataBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\BrowserData;
use Ingenico\Connect\Test\Unit\AbstractTestCase;

class BrowserDataBuilderTest extends AbstractTestCase
{
    /**
     * @var BrowserDataBuilder
     */
    private $subject;

    protected function setUp()
    {
        $this->subject = $this->getObjectManager()->getObject(
            BrowserDataBuilder::class,
            [
                'browserDataFactory' => $this->getMockForFactory(BrowserData::class),
            ]
        );
    }

    public function testJavascriptIsAlwaysEnabled()
    {
        // Exercise:
        $result = $this->subject->create();

        // Verify:
        self::assertTrue($result->javaScriptEnabled);
    }
}
