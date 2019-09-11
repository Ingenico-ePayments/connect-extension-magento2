<?php

namespace Ingenico\Connect\Test\Unit\Model\Ingenico\RequestBuilder\MethodSpecificInput;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\MethodSpecificInput\CardDecorator;
use Ingenico\Connect\Model\Ingenico\Token\TokenServiceInterface;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CardPaymentMethodSpecificInput;
use Ingenico\Connect\Test\Unit\AbstractTestCase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\MockObject\MockObject;

class CardDecoratorTest extends AbstractTestCase
{
    /**
     * @var CardDecorator
     */
    private $subject;

    /**
     * @var TokenServiceInterface|MockObject
     */
    private $mockedTokenService;

    protected function setUp()
    {
        $this->mockedTokenService = $this->getMockBuilder(TokenServiceInterface::class)->getMock();

        $this->subject = $this->getObjectManager()->getObject(
            CardDecorator::class,
            [
                'cardPaymentMethodSpecificInputFactory' =>
                    $this->getMockForFactory(CardPaymentMethodSpecificInput::class),
                'tokenService'                          => $this->mockedTokenService,
            ]
        );
    }

    public function testPaymentTransactionChannel()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        $mockedPayment->method('getAdditionalInformation')->willReturnMap([]);
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);

        // Exercise:
        $result = $this->subject->decorate(new CreatePaymentRequest(), $mockedOrder)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertSame(CardDecorator::TRANSACTION_CHANNEL, $result->transactionChannel);
    }

    public function testTokenizeIsFalseForGuestCustomers()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        $mockedPayment->method('getAdditionalInformation')->willReturnMap([]);
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $mockedOrder->method('getCustomerIsGuest')->willReturn(true);

        // Exercise:
        $result = $this->subject->decorate(new CreatePaymentRequest(), $mockedOrder)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertFalse($result->tokenize);
    }

    public function testTokenizeIsFalseIfCustomerWantsNoTokenization()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        $mockedPayment->method('getAdditionalInformation')->willReturnMap([
            [Config::PRODUCT_TOKENIZE_KEY, ''],
        ]);
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);
        $this->mockedTokenService->method('find')->willReturn([]);

        // Exercise:
        $result = $this->subject->decorate(new CreatePaymentRequest(), $mockedOrder)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertFalse($result->tokenize);
    }

    public function testTokenizeIsTrueIfCustomerWantsTokenization()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        $mockedPayment->method('getAdditionalInformation')->willReturnMap([
            [Config::PRODUCT_TOKENIZE_KEY, '1'],
        ]);
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);
        $this->mockedTokenService->method('find')->willReturn(['some-token-1234']);

        // Exercise:
        $result = $this->subject->decorate(new CreatePaymentRequest(), $mockedOrder)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertTrue($result->tokenize);
    }

    public function testRegisteredCustomerSaveNewCardInFile()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        $mockedPayment->method('getAdditionalInformation')->willReturnMap([
            [Config::PRODUCT_TOKENIZE_KEY, '1'],
        ]);
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);

        // Exercise:
        $result = $this->subject->decorate(new CreatePaymentRequest(), $mockedOrder)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertEquals(
            CardDecorator::UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED,
            $result->unscheduledCardOnFileRequestor
        );
        self::assertEquals(
            CardDecorator::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_FIRST,
            $result->unscheduledCardOnFileSequenceIndicator
        );
    }

    public function testRegisteredCustomerUseExistingCardInFile()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        $mockedPayment->method('getAdditionalInformation')->willReturnMap([
            [Config::PRODUCT_TOKENIZE_KEY, ''],
            [Config::CLIENT_PAYLOAD_IS_PAYMENT_ACCOUNT_ON_FILE, '1'],
        ]);
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);

        // Exercise:
        $result = $this->subject->decorate(new CreatePaymentRequest(), $mockedOrder)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertEquals(
            CardDecorator::UNSCHEDULED_CARD_ON_FILE_REQUESTOR_CARDHOLDER_INITIATED,
            $result->unscheduledCardOnFileRequestor
        );
        self::assertEquals(
            CardDecorator::UNSCHEDULED_CARD_ON_FILE_SEQUENCE_INDICATOR_SUBSEQUENT,
            $result->unscheduledCardOnFileSequenceIndicator
        );
    }

    public function testRegisteredCustomerDoNotSaveCardOnFile()
    {
        // Setup:
        /** @var OrderInterface|MockObject $mockedOrder */
        $mockedOrder = $this->getMockBuilder(OrderInterface::class)->getMock();
        /** @var OrderPaymentInterface|MockObject $mockedPayment */
        $mockedPayment = $this->getMockBuilder(OrderPaymentInterface::class)->getMock();
        $mockedPayment->method('getAdditionalInformation')->willReturnMap([
            [Config::PRODUCT_TOKENIZE_KEY, ''],
            [Config::CLIENT_PAYLOAD_IS_PAYMENT_ACCOUNT_ON_FILE, ''],
        ]);
        $mockedOrder->method('getPayment')->willReturn($mockedPayment);
        $mockedOrder->method('getCustomerIsGuest')->willReturn(false);
        $mockedOrder->method('getCustomerId')->willReturn(1);

        // Exercise:
        $result = $this->subject->decorate(new CreatePaymentRequest(), $mockedOrder)->cardPaymentMethodSpecificInput;

        // Verify:
        self::assertNull($result->unscheduledCardOnFileRequestor);
        self::assertNull($result->unscheduledCardOnFileSequenceIndicator);
    }
}
