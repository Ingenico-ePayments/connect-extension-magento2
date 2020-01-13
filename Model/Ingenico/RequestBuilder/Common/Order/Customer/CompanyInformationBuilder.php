<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer;

use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformation;
use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformationFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class CompanyInformationBuilder
{
    /** @var LoggerInterface */
    private $logger;

    /** @var CompanyInformationFactory */
    private $companyInformationFactory;

    /** @var CustomerRepositoryInterface */
    private $customerRepository;

    public function __construct(
        LoggerInterface $logger,
        CompanyInformationFactory $companyInformationFactory,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->companyInformationFactory = $companyInformationFactory;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
    }

    public function create(OrderInterface $order): CompanyInformation
    {
        $companyInformation = $this->companyInformationFactory->create();
        $companyInformation->vatNumber = $order->getCustomerTaxvat();

        if ($order->getCustomerTaxvat() !== null || !$order->getCustomerId()) {
            return $companyInformation;
        }

        $companyInformation->vatNumber = $this->getCustomerTaxvat((int) $order->getCustomerId());

        return $companyInformation;
    }

    protected function getCustomerTaxvat(int $customerId): ?string
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getTaxvat();
        } catch (\Exception $exception) {
            $this->logger->error(sprintf('Exception while retrieving customer taxvat: %s', $exception->getMessage()));
            return null;
        }
    }
}
