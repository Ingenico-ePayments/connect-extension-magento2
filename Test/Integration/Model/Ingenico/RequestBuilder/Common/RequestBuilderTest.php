<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\Common;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\Account\AuthenticationBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\CustomerBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;

class RequestBuilderTest extends AbstractRequestBuilderTestCase
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
     * @magentoConfigFixture current_store contact/contact/enabled 1
     */
    public function testCreateRequestForGuestCustomer()
    {
        // Setup:
        $order = $this->orderFixture->createOrder();

        // Exercise:
        $request = $this->requestBuilder->create(new CreatePaymentRequest(), $order);

        // Verify:
        self::assertSame(
            AuthenticationBuilder::GUEST,
            $request->order->customer->account->authentication->method
        );
        self::assertEquals(
            CustomerBuilder::ACCOUNT_TYPE_NONE,
            $request->order->customer->accountType
        );
        self::assertEquals('http://localhost/index.php/contact/', $request->merchant->contactWebsiteUrl);
        self::assertEquals('http://localhost/index.php/', $request->merchant->websiteUrl);
        self::assertNull($request->order->customer->account->authentication->utcTimestamp);
        self::assertNull($request->order->customer->account->createDate);
        self::assertNull($request->order->customer->account->changeDate);
        self::assertNull($request->order->customer->account->hadSuspiciousActivity);
        self::assertNull($request->order->customer->account->paymentActivity->numberOfPurchasesLast6Months);
        self::assertNull($request->order->customer->account->paymentActivity->numberOfPaymentAttemptsLastYear);
        self::assertNull($request->order->customer->account->paymentActivity->numberOfPaymentAttemptsLast24Hours);
        self::assertTrue($request->order->customer->device->browserData->javaScriptEnabled);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testCreateRequestForLoggedInCustomer()
    {
        // Setup:
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);
        $this->customerFixture->updateCustomerAddressesUpdatedAt($customer, '2019-08-01 10:45:00');

        // Exercise:
        $request = $this->requestBuilder->create(new CreatePaymentRequest(), $order);

        // Verify:
        self::assertSame(
            AuthenticationBuilder::MERCHANT_CREDENTIALS,
            $request->order->customer->account->authentication->method
        );
        self::assertEquals(
            CustomerBuilder::ACCOUNT_TYPE_EXISTING,
            $request->order->customer->accountType
        );
        self::assertEquals('201908011100', $request->order->customer->account->authentication->utcTimestamp);
        self::assertEquals('20190601', $request->order->customer->account->createDate);
        self::assertEquals('20190801', $request->order->customer->account->changeDate);
        self::assertFalse($request->order->customer->account->hadSuspiciousActivity);
        self::assertSame(0, $request->order->customer->account->paymentActivity->numberOfPurchasesLast6Months);
        self::assertEquals(
            0,
            $request->order->customer->account->paymentActivity->numberOfPaymentAttemptsLastYear
        );
        self::assertEquals(
            0,
            $request->order->customer->account->paymentActivity->numberOfPaymentAttemptsLast24Hours
        );
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testCreateRequestForLoggedInCustomerWithAccountChangedOnCustomer()
    {
        // Setup:
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);
        $this->customerFixture->updateCustomerAddressesUpdatedAt($customer, '2018-08-01 10:45:00');

        // Exercise:
        $request = $this->requestBuilder->create(new CreatePaymentRequest(), $order);

        // Verify:
        self::assertEquals('20190701', $request->order->customer->account->changeDate);
    }
}
