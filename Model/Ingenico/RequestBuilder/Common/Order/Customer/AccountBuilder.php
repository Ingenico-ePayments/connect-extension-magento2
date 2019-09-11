<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer;

use DateTime;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\Account\AuthenticationBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\Account\PaymentActivityBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerAccount;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerAccountFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory as CustomerAddressCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class AccountBuilder
{
    /**
     * @var CustomerAccountFactory
     */
    private $customerAccountFactory;

    /**
     * @var PaymentActivityBuilder
     */
    private $paymentActivityBuilder;

    /**
     * @var AuthenticationBuilder
     */
    private $authenticationBuilder;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var CustomerAddressCollectionFactory
     */
    private $customerAddressCollectionFactory;

    public function __construct(
        CustomerAccountFactory $customerAccountFactory,
        AuthenticationBuilder $authenticationBuilder,
        PaymentActivityBuilder $paymentActivityBuilder,
        CustomerRepositoryInterface $customerRepository,
        CustomerAddressCollectionFactory $customerAddressCollectionFactory,
        DateTimeFactory $dateTimeFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->customerAccountFactory = $customerAccountFactory;
        $this->authenticationBuilder = $authenticationBuilder;
        $this->paymentActivityBuilder = $paymentActivityBuilder;
        $this->customerRepository = $customerRepository;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerAddressCollectionFactory = $customerAddressCollectionFactory;
    }

    public function create(OrderInterface $order): CustomerAccount
    {
        /** @var CustomerAccount $customerAccount */
        $customerAccount = $this->customerAccountFactory->create();
        $customerAccount->authentication = $this->authenticationBuilder->create($order);
        $customerAccount->paymentActivity = $this->paymentActivityBuilder->create($order);

        try {
            $customerAccount->createDate = $this->getCustomerCreateDate($order);
            $customerAccount->changeDate = $this->getCustomerChangeDate($order);
            $customerAccount->hadSuspiciousActivity = $this->getCustomerHadSuspiciousActivity($order);
        } catch (LocalizedException $exception) {
            // Do nothing
        }

        return $customerAccount;
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerCreateDate(OrderInterface $order): string
    {
        if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
            throw new LocalizedException(__('Cannot get customer create date'));
        }

        $customer = $this->customerRepository->getById($order->getCustomerId());
        return $this->dateTimeFactory->create($customer->getCreatedAt())->format('Ymd');
    }

    /**
     * @param OrderInterface $order
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomerChangeDate(OrderInterface $order): string
    {
        if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
            throw new LocalizedException(__('Cannot get customer change date'));
        }
        $customer = $this->customerRepository->getById($order->getCustomerId());
        $customerUpdatedAt = $this->dateTimeFactory->create($customer->getUpdatedAt());
        $latestCustomerAddressUpdatedAt = $this->getLatestCustomerAddressUpdatedAt($customer->getId());

        return $latestCustomerAddressUpdatedAt > $customerUpdatedAt ?
            $latestCustomerAddressUpdatedAt->format('Ymd') :
            $customerUpdatedAt->format('Ymd');
    }

    /**
     * @param OrderInterface $order
     * @return bool
     * @throws LocalizedException
     */
    private function getCustomerHadSuspiciousActivity(OrderInterface $order): bool
    {
        if ($order->getCustomerIsGuest() || !$order->getCustomerId()) {
            throw new LocalizedException(__('Cannot get customer fraud for a guest'));
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(OrderInterface::CUSTOMER_ID, $order->getCustomerId())
            ->addFilter(OrderInterface::STATUS, Order::STATUS_FRAUD)
            ->create();

        $customerOrders = $this->orderRepository->getList($searchCriteria);
        return $customerOrders->getTotalCount() > 0;
    }

    private function getLatestCustomerAddressUpdatedAt(int $customerId): DateTime
    {
        /** @var Address $customerAddress */
        return $this->dateTimeFactory->create(
            $this->customerAddressCollectionFactory->create()
                ->addFieldToFilter('parent_id', $customerId)
                ->addAttributeToSort('updated_at', 'DESC')
                ->setPageSize(1)
                ->getFirstItem()
                ->getData('updated_at')
        );
    }
}
