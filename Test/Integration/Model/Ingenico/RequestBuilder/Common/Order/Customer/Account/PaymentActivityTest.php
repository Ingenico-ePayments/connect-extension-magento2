<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\Common\Order\Customer\Account;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;

class PaymentActivityTest extends AbstractRequestBuilderTestCase
{
    /**
     * @var RequestBuilder
     */
    private $requestBuilder;

    protected function setUp()
    {
        parent::setUp();
        $this->requestBuilder = $this->objectManager->get(RequestBuilder::class);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testCreateRequestForCustomersWithPreviousOrders()
    {
        // Setup:
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);
        $this->orderFixture->setOrderCreationDate($this->orderFixture->createOrder($customer), 'now -30 minutes');
        $this->orderFixture->setOrderCreationDate($this->orderFixture->createOrder($customer), 'now -2 days');
        $this->orderFixture->setOrderCreationDate($this->orderFixture->createOrder($customer), 'now -7 months');
        $this->orderFixture->setOrderCreationDate($this->orderFixture->createOrder($customer), 'now -11 months');
        $this->orderFixture->setOrderCreationDate($this->orderFixture->createOrder($customer), 'now -2 years');

        // Exercise:
        $request = $this->requestBuilder->create(new CreatePaymentRequest(), $order);

        self::assertSame(2, $request->order->customer->account->paymentActivity->numberOfPurchasesLast6Months);
        self::assertSame(
            4,
            $request->order->customer->account->paymentActivity->numberOfPaymentAttemptsLastYear
        );
        self::assertEquals(
            1,
            $request->order->customer->account->paymentActivity->numberOfPaymentAttemptsLast24Hours
        );
    }
}
