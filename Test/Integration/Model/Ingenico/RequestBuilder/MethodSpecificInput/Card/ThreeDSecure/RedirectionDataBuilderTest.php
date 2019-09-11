<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\MethodSpecificInput\Card\ThreeDSecure;

use Exception;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\CardDecorator;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;

class RedirectionDataBuilderTest extends AbstractRequestBuilderTestCase
{
    const EXPECTED_HOSTED_CHECKOUT_RETURN_URL =
        'http://localhost/index.php/epayments/hostedCheckoutPage/processReturn/';

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
    public function testCreateRequestForHostedCheckoutWithGuestCustomer()
    {
        // Setup:
        $request = $this->createPaymentRequest();
        $order = $this->orderFixture->createOrder();

        // Exercise:
        $request = $this->cardDecorator->decorate($request, $order);

        // Verify:
        self::assertEquals(
            self::EXPECTED_HOSTED_CHECKOUT_RETURN_URL,
            $request->cardPaymentMethodSpecificInput->threeDSecure->redirectionData->returnUrl
        );
    }
}
