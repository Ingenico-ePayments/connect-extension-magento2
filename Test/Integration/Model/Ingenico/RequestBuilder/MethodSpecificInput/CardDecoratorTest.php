<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Exception;
use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\CardDecorator;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;

/**
 * This integration test checks if all the 3DSv2 properties are added to
 * the payment request in the card decorator (since cards are the only
 * payment methods that utilize 3DSv2).
 *
 * Class RequestBuilderTest
 */
class CardDecoratorTest extends AbstractRequestBuilderTestCase
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
        self::assertEquals(
            CardDecorator::TRANSACTION_CHANNEL,
            $request->cardPaymentMethodSpecificInput->transactionChannel
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testRegisteredCustomerSaveNewCardInFile()
    {
        // Setup:
        $request = $this->createPaymentRequest();
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder(
            $customer,
            null,
            [
                Config::PRODUCT_TOKENIZE_KEY => '1',
            ]
        );

        // Exercise:
        $request = $this->cardDecorator->decorate($request, $order)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertEquals(
            CardDecorator::UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED,
            $request->unscheduledCardOnFileRequestor
        );
        self::assertEquals(
            CardDecorator::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_FIRST,
            $request->unscheduledCardOnFileSequenceIndicator
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testRegisteredCustomerUseExistingCardInFile()
    {
        // Setup:
        $request = $this->createPaymentRequest();
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder(
            $customer,
            null,
            [
                Config::PRODUCT_TOKENIZE_KEY                      => '',
                Config::CLIENT_PAYLOAD_IS_PAYMENT_ACCOUNT_ON_FILE => '1',
            ]
        );

        // Exercise:
        $request = $this->cardDecorator->decorate($request, $order)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertEquals(
            CardDecorator::UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED,
            $request->unscheduledCardOnFileRequestor
        );
        self::assertEquals(
            CardDecorator::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_SUBSEQUENT,
            $request->unscheduledCardOnFileSequenceIndicator
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testRegisteredCustomerDoNotSaveCardOnFile()
    {
        // Setup:
        $request = $this->createPaymentRequest();
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder(
            $customer,
            null,
            [
                Config::PRODUCT_TOKENIZE_KEY                      => '',
                Config::CLIENT_PAYLOAD_IS_PAYMENT_ACCOUNT_ON_FILE => '',
            ]
        );

        // Exercise:
        $request = $this->cardDecorator->decorate($request, $order)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertNull($request->unscheduledCardOnFileRequestor);
        self::assertNull($request->unscheduledCardOnFileSequenceIndicator);
    }
}
