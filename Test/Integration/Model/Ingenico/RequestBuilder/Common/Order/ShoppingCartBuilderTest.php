<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\Common\Order;

use Exception;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;

class ShoppingCartBuilderTest extends AbstractRequestBuilderTestCase
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
     * @throws Exception
     */
    public function testCreateRequestForLoggedInCustomerWithoutPreviousOrderCompleted()
    {
        // Setup:
        $request = $this->createPaymentRequest();
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->setOrderAsProcessing($this->orderFixture->createOrder($customer));

        // Exercise:
        $request = $this->requestBuilder->create($request, $order);

        self::assertFalse($request->order->shoppingCart->reOrderIndicator);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testCreateRequestForLoggedInCustomerWithDifferentProductPreviouslyOrdered()
    {
        // Setup:
        $request = $this->createPaymentRequest();
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);
        $order = $this->orderFixture->setOrderAsProcessing($order);

        $previousOrderProduct = $this->productFixture->createProduct('sku2');
        $this->orderFixture->createOrder($customer, $previousOrderProduct);

        // Exercise:
        $request = $this->requestBuilder->create($request, $order);

        self::assertFalse($request->order->shoppingCart->reOrderIndicator);
    }

    /**
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     * @throws Exception
     */
    public function testCreateRequestForLoggedInCustomerWithPreviouslyOrders()
    {
        // Setup:
        $request = $this->createPaymentRequest();
        $customer = $this->customerFixture->createCustomer();
        $order = $this->orderFixture->createOrder($customer);
        $order = $this->orderFixture->setOrderAsProcessing($order);
        $this->orderFixture->createOrder($customer);

        // Exercise:
        $request = $this->requestBuilder->create($request, $order);

        self::assertTrue($request->order->shoppingCart->reOrderIndicator);
    }
}
