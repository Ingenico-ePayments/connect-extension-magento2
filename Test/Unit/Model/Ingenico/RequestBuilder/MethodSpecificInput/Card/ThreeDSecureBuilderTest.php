<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecureBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ThreeDSecure;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Sales\Api\Data\OrderInterface;
use PHPUnit\Framework\MockObject\MockObject;

class ThreeDSecureBuilderTest extends AbstractTestCase
{
    /**
     * @var ThreeDSecureBuilder
     */
    private $subject;

    protected function setUp()
    {
        $this->subject = $this->getObjectManager()->getObject(
            ThreeDSecureBuilder::class,
            [
                'threeDSecureFactory' => $this->getMockForFactory(ThreeDSecure::class),
            ]
        );
    }

    public function testAuthenticationFlow()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();

        // Exercise:
        $result = $this->subject->create($mockedOrder);

        // Verify:
        self::assertEquals(ThreeDSecureBuilder::AUTHENTICATION_FLOW_BROWSER, $result->authenticationFlow);
    }
}
