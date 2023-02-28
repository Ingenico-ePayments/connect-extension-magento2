<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer;

use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformation;
use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformationFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;

class CompanyInformationBuilder
{
    /** @var LoggerInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $logger;

    /** @var CompanyInformationFactory */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $companyInformationFactory;

    /** @var CustomerRepositoryInterface */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
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
        $companyInformation->vatNumber = $this->getOrderTaxvat($order);
        if ($companyInformation->vatNumber !== null || !$order->getCustomerId()) {
            return $companyInformation;
        }

        $companyInformation->vatNumber = $this->getCustomerTaxvat((int) $order->getCustomerId());
        return $companyInformation;
    }

    protected function getOrderTaxvat(OrderInterface $order): ?string
    {
        if ($order->getBillingAddress() && $order->getBillingAddress()->getVatId() !== '') {
            return $order->getBillingAddress()->getVatId();
        }

        return null;
    }

    protected function getCustomerTaxvat(int $customerId): ?string
    {
        try {
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getTaxvat();
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
        } catch (\Exception $exception) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $this->logger->error(sprintf('Exception while retrieving customer taxvat: %s', $exception->getMessage()));
            return null;
        }
    }
}
