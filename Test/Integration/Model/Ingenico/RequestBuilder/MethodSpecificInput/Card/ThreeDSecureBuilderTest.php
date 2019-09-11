<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card;

use Exception;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecureBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\CardDecorator;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;

class ThreeDSecureBuilderTest extends AbstractRequestBuilderTestCase
{
    /**
     * @var CardDecorator
     */
    private $cardDecorator;

    protected function setUp()
    {
        parent::setUp();
        $this->cardDecorator = $this->objectManager->get(CardDecorator::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testCreateRequestForGuestCustomer()
    {
        // Setup:
        $request = $this->createPaymentRequest();
        $order = $this->orderFixture->createOrder();

        // Exercise:
        $request = $this->cardDecorator->decorate($request, $order);

        // Verify:
        self::assertNull($request->cardPaymentMethodSpecificInput->skipAuthentication);
        self::assertEquals(
            ThreeDSecureBuilder::AUTHENTICATION_FLOW_BROWSER,
            $request->cardPaymentMethodSpecificInput->threeDSecure->authenticationFlow
        );
    }
}
