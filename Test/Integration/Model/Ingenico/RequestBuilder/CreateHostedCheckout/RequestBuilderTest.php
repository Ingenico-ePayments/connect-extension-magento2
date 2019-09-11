<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\CreateHostedCheckout;

use Ingenico\Connect\Model\Config;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\CreateHostedCheckout\RequestBuilder;
use Ingenico\Connect\Model\Ingenico\Token\TokenServiceInterface;
use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformation;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\BrowserData;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Customer;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerAccount;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerAccountAuthentication;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerDevice;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerPaymentActivity;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Merchant;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Order;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalName;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Shipping;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ShoppingCart;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;
use Magento\Sales\Api\OrderRepositoryInterface;

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
     */
    public function testCustomerThatHasNoTokensThatWantsNoTokenization()
    {
        // Setup:
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);

        // Exercise:
        $request = $this->requestBuilder->create($order);

        // Verify:
        self::assertFalse($request->cardPaymentMethodSpecificInput->tokenize);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testCustomerThatHasNoTokensThatWantsTokenization()
    {
        // Setup:
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);
        $order->getPayment()->setAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY, '1');
        $order = $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        // Exercise:
        $request = $this->requestBuilder->create($order);

        // Verify:
        self::assertTrue($request->cardPaymentMethodSpecificInput->tokenize);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testCustomerThatHasTokensThatWantsNoTokenization()
    {
        // Setup:
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);
        /** @var TokenServiceInterface $tokenService */
        $tokenService = $this->objectManager->get(TokenServiceInterface::class);
        $tokenService->add($customer->getId(), 1, 'foo-123');
        $tokenService->add($customer->getId(), 1, 'bar-456');
        $tokenService->add($customer->getId(), 2, 'bazz-789');

        // Exercise:
        $request = $this->requestBuilder->create($order);

        // Verify:
        self::assertFalse($request->cardPaymentMethodSpecificInput->tokenize);
        self::assertEquals('bar-456,foo-123', $request->hostedCheckoutSpecificInput->tokens);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testCustomerThatHasTokensThatWantsTokenization()
    {
        // Setup:
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);
        /** @var TokenServiceInterface $tokenService */
        $tokenService = $this->objectManager->get(TokenServiceInterface::class);
        $tokenService->add($customer->getId(), 1, 'foo-123');
        $tokenService->add($customer->getId(), 1, 'bar-456');
        $tokenService->add($customer->getId(), 2, 'bazz-789');
        $order->getPayment()->setAdditionalInformation(Config::PRODUCT_TOKENIZE_KEY, '1');
        $order = $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        // Exercise:
        $request = $this->requestBuilder->create($order);

        // Verify:
        self::assertTrue($request->cardPaymentMethodSpecificInput->tokenize);
        self::assertEquals('bar-456,foo-123', $request->hostedCheckoutSpecificInput->tokens);
    }

    /**
     * @return CreateHostedCheckoutRequest
     */
    protected function createHostedCheckoutRequest(): CreateHostedCheckoutRequest
    {
        $fakePaymentRequest = new CreateHostedCheckoutRequest();
        $fakePaymentRequest->merchant = new Merchant();
        $fakePaymentRequest->order = new Order();
        $fakePaymentRequest->order->customer = new Customer();
        $fakePaymentRequest->order->customer->account = new CustomerAccount();
        $fakePaymentRequest->order->customer->account->authentication = new CustomerAccountAuthentication();
        $fakePaymentRequest->order->customer->account->paymentActivity = new CustomerPaymentActivity();
        $fakePaymentRequest->order->customer->companyInformation = new CompanyInformation();
        $fakePaymentRequest->order->customer->device = new CustomerDevice();
        $fakePaymentRequest->order->customer->device->browserData = new BrowserData();
        $fakePaymentRequest->order->shipping = new Shipping();
        $fakePaymentRequest->order->shipping->address = new AddressPersonal();
        $fakePaymentRequest->order->shipping->address->name = new PersonalName();
        $fakePaymentRequest->order->shoppingCart = new ShoppingCart();

        return $fakePaymentRequest;
    }
}
