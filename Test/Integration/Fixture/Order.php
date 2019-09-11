<?php

namespace Ingenico\Connect\Test\Integration\Fixture;

use DateTime;
use Exception;
use Ingenico\Connect\Model\Config;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as MagentoOrder;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

class Order
{
    const REMOTE_IP = '123.4.5.6';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var int
     */
    private $lastIncrementId = 0;

    /**
     * @var Product
     */
    private $productFixture;

    public function __construct()
    {
        $this->objectManager = Bootstrap::getObjectManager();
        $this->productFixture = new Product();
    }

    /**
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws StateException
     */
    public function createOrder(
        CustomerInterface $customer = null,
        ProductInterface $product = null,
        array $additionalInformation = []
    ): OrderInterface {
        $payment = $this->objectManager->create(Payment::class);
        $payment->setMethod('ingenico')->setAdditionalInformation(
            array_merge(
                [
                    Config::PRODUCT_ID_KEY => 1,
                    Config::PRODUCT_LABEL_KEY => 'Visa',
                    Config::PRODUCT_TOKENIZE_KEY => '',
                    Config::PRODUCT_PAYMENT_METHOD_KEY => 'card',
                    Config::CLIENT_PAYLOAD_KEY => '',
                    'method_title' => 'Ingenico Connect',
                    Config::IDEMPOTENCE_KEY => '.5d42e11b7d8a68.46151250',
                    Config::REDIRECT_URL_KEY => 'https://www.example.com/redirect',
                    Config::HOSTED_CHECKOUT_ID_KEY => '1001',
                    Config::RETURNMAC_KEY => '1002',
                ],
                $additionalInformation
            )
        );

        $billingAddress = $this->objectManager->create(Address::class, ['data' => OrderAddress::BILLING_ADDRESS_DATA]);
        $shippingAddress = $this->objectManager->create(
            Address::class,
            ['data' => OrderAddress::SHIPPING_ADDRESS_DATA]
        );

        if ($product === null) {
            $product = $this->productFixture->createProduct();
        }

        /** @var OrderItemInterface $orderItem */
        $orderItem = $this->objectManager->create(OrderItemInterface::class);
        $orderItem->setProductId($product->getId())
            ->setQtyOrdered(1)
            ->setBasePrice($product->getPrice())
            ->setPrice($product->getPrice())
            ->setRowTotal($product->getPrice())
            ->setProductType($product->getType())
            ->setSku($product->getSku());

        /** @var OrderInterface $order */
        $this->lastIncrementId += 1;
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setIncrementId(sprintf('TEST_%1$d', $this->lastIncrementId))
            ->setSubtotal($product->getPrice())
            ->setGrandTotal($product->getPrice())
            ->setBaseSubtotal($product->getPrice())
            ->setBaseGrandTotal($product->getPrice())
            ->setTotalDue(0.00)
            ->setBaseTotalDue(0.00)
            ->setTotalPaid($product->getPrice())
            ->setBaseTotalPaid($product->getPrice())
            ->setCustomerIsGuest($customer === null ? 1 : 0)
            ->setCustomerEmail(OrderAddress::BILLING_ADDRESS_DATA[OrderAddressInterface::EMAIL])
            ->setCustomerTaxvat(OrderAddress::BILLING_ADDRESS_DATA[OrderAddressInterface::VAT_ID])
            ->setBillingAddress($billingAddress)
            ->setRemoteIp(self::REMOTE_IP)
            ->setShippingAddress($shippingAddress)
            ->setStoreId($this->objectManager->get(StoreManagerInterface::class)->getStore()->getId())
            ->addItem($orderItem)
            ->setBaseCurrencyCode('USD')
            ->setPayment($payment);
        $order->setState(MagentoOrder::STATE_COMPLETE);
        $order->setStatus(MagentoOrder::STATE_COMPLETE);

        if ($customer !== null) {
            $order->setCustomerId($customer->getId());
        }

        $order = $this->getOrderRepository()->save($order);

        return $order;
    }

    public function getOrderRepository(): OrderRepositoryInterface
    {
        return $this->objectManager->get(OrderRepositoryInterface::class);
    }

    /**
     * @return int
     */
    public function getLastIncrementId(): int
    {
        return $this->lastIncrementId;
    }

    /**
     * @param OrderInterface $order
     * @param string $newCreationDateTime
     * @throws Exception
     */
    public function setOrderCreationDate(OrderInterface $order, string $newCreationDateTime)
    {
        $newCreationDate = new DateTime($newCreationDateTime);
        $order->setCreatedAt($newCreationDate->format('Y-m-d H:i:s'));
        $this->getOrderRepository()->save($order);
    }

    public function setOrderAsProcessing(OrderInterface $order): OrderInterface
    {
        $order->setState(MagentoOrder::STATE_PROCESSING);
        $order->setStatus(MagentoOrder::STATE_PROCESSING);
        return $this->getOrderRepository()->save($order);
    }
}
