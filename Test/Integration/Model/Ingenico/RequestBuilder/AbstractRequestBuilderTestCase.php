<?php

namespace Ingenico\Connect\Test\Integration\Model\Ingenico\RequestBuilder;

use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformation;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
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
use Ingenico\Connect\Test\Integration\Fixture\Customer as CustomerFixture;
use Ingenico\Connect\Test\Integration\Fixture\Order as OrderFixture;
use Ingenico\Connect\Test\Integration\Fixture\Product as ProductFixture;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ShoppingCart;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

abstract class AbstractRequestBuilderTestCase extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var OrderFixture
     */
    protected $orderFixture;

    /**
     * @var CustomerFixture
     */
    protected $customerFixture;

    /**
     * @var ProductFixture
     */
    protected $productFixture;

    protected function setUp()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->orderFixture = new OrderFixture();
        $this->customerFixture = new CustomerFixture();
        $this->productFixture = new ProductFixture();
    }

    /**
     * @return CreatePaymentRequest
     */
    protected function createPaymentRequest(): CreatePaymentRequest
    {
        $fakePaymentRequest = new CreatePaymentRequest();
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
