<?php

namespace Ingenico\Connect\Test\Integration\Fixture;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Logger;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;

class Customer
{
    const CUSTOMER_CREATED_AT = '2019-06-01 12:15:00';
    const CUSTOMER_UPDATED_AT = '2019-07-01 10:45:00';
    const CUSTOMER_LAST_LOGIN_AT = '2019-08-01 11:00:00';
    const CUSTOMER_LAST_LOGOUT_AT = '2019-08-02 12:00:00';

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct()
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @return CustomerInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    public function createCustomer(): CustomerInterface
    {
        /** @var CustomerInterface $customer */
        $customer = $this->objectManager->create(CustomerInterface::class);
        /** @var CustomerRepositoryInterface $customerRepository */
        $customerRepository = $this->objectManager->get(CustomerRepositoryInterface::class);
        /** @var AddressInterface $billingAddress */
        $billingAddress = $this->objectManager->create(
            AddressInterface::class,
            ['data' => $this->updateRegion(OrderAddress::BILLING_ADDRESS_DATA)]
        );
        $billingAddress->setIsDefaultBilling(true);
        /** @var AddressInterface $shippingAddress */
        $shippingAddress = $this->objectManager->create(
            AddressInterface::class,
            ['data' => $this->updateRegion(OrderAddress::SHIPPING_ADDRESS_DATA)]
        );
        $shippingAddress->setIsDefaultShipping(true);

        $customer->setEmail('jane.doe@example.com');
        $customer->setFirstname('Jane');
        $customer->setLastname('Doe');
        $customer->setAddresses([$billingAddress, $shippingAddress]);
        $customer->setDob('1984-05-25');
        $customer->setGender(2);
        $customer->setCreatedAt(self::CUSTOMER_CREATED_AT);
        $customer = $customerRepository->save($customer);

        // We need to explicitly update the updated_at in the database,
        // because the customerRepository::save() method will override it by default.
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $connection->update(
            $resource->getTableName('customer_entity'),
            ['updated_at' => self::CUSTOMER_UPDATED_AT],
            ['entity_id' => $customer->getId()]
        );

        /** @var CustomerRegistry $customerRegistry */
        $customerRegistry = $this->objectManager->get(CustomerRegistry::class);
        $customerRegistry->remove($customer->getId());
        $customer = $customerRepository->getById($customer->getId());

        $this->getLogger()->log($customer->getId(), [
            'last_login_at'  => self::CUSTOMER_LAST_LOGIN_AT,
            'last_logout_at' => self::CUSTOMER_LAST_LOGOUT_AT,
        ]);

        return $customer;
    }

    public function getLogger(): Logger
    {
        return $this->objectManager->get(Logger::class);
    }

    private function updateRegion(array $data): array
    {
        if (isset($data[AddressInterface::REGION])) {
            $region = $data[AddressInterface::REGION];
            if (is_string($region)) {
                /** @var RegionInterface $regionObject */
                $regionObject = $this->objectManager->create(RegionInterface::class);
                $regionObject->setRegion($data[AddressInterface::REGION]);
                $regionObject->setRegionCode($data[AddressInterface::REGION_ID] ?? null);
                $data[AddressInterface::REGION] = $regionObject;
            }
        }

        return $data;
    }

    public function updateCustomerAddressesUpdatedAt(CustomerInterface $customer, string $date)
    {
        $addresses = $customer->getAddresses();
        foreach ($addresses as $address) {
            /** @var ResourceConnection $resource */
            $resource = $this->objectManager->get(ResourceConnection::class);
            $connection = $resource->getConnection();
            $connection->update(
                $resource->getTableName('customer_address_entity'),
                ['updated_at' => $date],
                ['entity_id' => $address->getId()]
            );
        }
    }
}
