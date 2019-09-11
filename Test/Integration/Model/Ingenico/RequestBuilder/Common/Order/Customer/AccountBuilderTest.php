<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\Common\Order\Customer;

use DateTime;
use Exception;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\RequestBuilder;
use Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder\AbstractRequestBuilderTestCase;
use Magento\Sales\Model\Order;

class AccountBuilderTest extends AbstractRequestBuilderTestCase
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
    public function testCreateRequestWithSuspiciousActivity()
    {
        $request = $this->createPaymentRequest();
        $customer = $this->customerFixture->createCustomer();

        $oldOrder = $order = $this->orderFixture->createOrder($customer);
        $oldOrder->setState(Order::STATE_CANCELED);
        $oldOrder->setStatus(Order::STATUS_FRAUD);
        $oldOrder->setCreatedAt((new DateTime('6 months ago'))->format('Y-m-d'));
        $this->orderFixture->getOrderRepository()->save($oldOrder);

        $order = $this->orderFixture->createOrder($customer);

        // Exercise:
        $request = $this->requestBuilder->create($request, $order);

        // Verify:
        self::assertTrue($request->order->customer->account->hadSuspiciousActivity);
    }
}
