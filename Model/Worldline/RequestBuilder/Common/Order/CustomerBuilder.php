<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order;

use Ingenico\Connect\Sdk\Domain\Definitions\AddressFactory;
use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformationFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ContactDetailsFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalInformationFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalNameFactory;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Worldline\Connect\Helper\Format;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer\AccountBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer\AddressBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer\CompanyInformationBuilder;
use Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Customer\DeviceBuilder;

/**
 * Class CustomerBuilder
 */
class CustomerBuilder
{
    public const EMAIL_MESSAGE_TYPE = 'html';
    public const GENDER_MALE = 0;
    public const GENDER_FEMALE = 1;
    public const ACCOUNT_TYPE_NONE = 'none';
    public const ACCOUNT_TYPE_EXISTING = 'existing';

    /**
     * @var CustomerFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $customerFactory;

    /**
     * @var PersonalInformationFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $personalInformationFactory;

    /**
     * @var CompanyInformationFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $companyInformationFactory;

    /**
     * @var ContactDetailsFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $contactDetailsFactory;

    /**
     * @var PersonalNameFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $personalNameFactory;

    /**
     * @var AddressFactory
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $addressFactory;

    /**
     * @var TimezoneInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $timezone;

    /**
     * @var AccountBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $accountBuilder;

    /**
     * @var DeviceBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $deviceBuilder;

    /**
     * @var CompanyInformationBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $companyInformationBuilder;

    /**
     * @var AddressBuilder
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $addressBuilder;

    /**
     * @var Format
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $format;

    /**
     * @var ResolverInterface
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $resolver;

    public function __construct(
        CustomerFactory $customerFactory,
        PersonalInformationFactory $personalInformationFactory,
        CompanyInformationFactory $companyInformationFactory,
        ContactDetailsFactory $contactDetailsFactory,
        PersonalNameFactory $personalNameFactory,
        AddressFactory $addressFactory,
        AddressBuilder $addressBuilder,
        AccountBuilder $accountBuilder,
        DeviceBuilder $deviceBuilder,
        CompanyInformationBuilder $companyInformationBuilder,
        TimezoneInterface $timezone,
        Format $format,
        ResolverInterface $resolver
    ) {
        $this->customerFactory = $customerFactory;
        $this->personalInformationFactory = $personalInformationFactory;
        $this->companyInformationFactory = $companyInformationFactory;
        $this->contactDetailsFactory = $contactDetailsFactory;
        $this->personalNameFactory = $personalNameFactory;
        $this->addressFactory = $addressFactory;
        $this->accountBuilder = $accountBuilder;
        $this->deviceBuilder = $deviceBuilder;
        $this->timezone = $timezone;
        $this->companyInformationBuilder = $companyInformationBuilder;
        $this->addressBuilder = $addressBuilder;
        $this->format = $format;
        $this->resolver = $resolver;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param OrderInterface $order
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Customer
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    public function create(OrderInterface $order)
    {
        $worldlineCustomer = $this->customerFactory->create();
        $worldlineCustomer->locale = $this->resolver->getLocale();

        $worldlineCustomer->personalInformation = $this->getPersonalInformation($order);
        // create dummy customer id
        $worldlineCustomer->merchantCustomerId = $this->format->limit(
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            $order->getCustomerId() ?: rand(100000, 999999),
            15
        );

        $billing = $order->getBillingAddress();
        // phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
        if (!empty($billing)) {
            $companyInformation = $this->companyInformationFactory->create();
            $companyInformation->name = $billing->getCompany();
            $worldlineCustomer->companyInformation = $companyInformation;

            $worldlineCustomer->contactDetails = $this->getContactDetails($order, $billing);
        }

        $worldlineCustomer->account = $this->accountBuilder->create($order);
        $worldlineCustomer->device = $this->deviceBuilder->create($order);
        $worldlineCustomer->accountType = $this->getAccountType($order);
        $worldlineCustomer->companyInformation = $this->companyInformationBuilder->create($order);
        $worldlineCustomer->billingAddress = $this->addressBuilder->create($order);

        return $worldlineCustomer;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param Order $order
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalInformation
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    private function getPersonalInformation(OrderInterface $order)
    {
        $personalInformation = $this->personalInformationFactory->create();

        $personalName = $this->personalNameFactory->create();
        $personalName->title = $order->getCustomerPrefix();
        $personalName->firstName = $this->format->limit($order->getCustomerFirstname(), 15);
        $personalName->surnamePrefix = $order->getCustomerMiddlename();
        $personalName->surname = $this->format->limit($order->getCustomerLastname(), 35);

        $personalInformation->name = $personalName;
        $personalInformation->gender = $this->getCustomerGender($order);
        $personalInformation->dateOfBirth = $this->getDateOfBirth($order);

        return $personalInformation;
    }

    /**
     * Extract binary gender as string representation
     *
     * @param OrderInterface $order
     * @return string
     */
    private function getCustomerGender(OrderInterface $order)
    {
        switch ($order->getCustomerGender()) {
            case self::GENDER_MALE:
                return 'male';
            case self::GENDER_FEMALE:
                return 'female';
            default:
                return 'unknown';
        }
    }

    /**
     * Extracts the date of birth in the API required format YYYYMMDD
     *
     * @param OrderInterface $order
     * @return string
     */
    private function getDateOfBirth(OrderInterface $order)
    {
        $dateOfBirth = '';
        if ($order->getCustomerDob()) {
            $doBObject = $this->timezone->date($order->getCustomerDob());
            $dateOfBirth = $doBObject->format('Ymd');
        }

        return $dateOfBirth;
    }

    // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    /**
     * @param Order $order
     * @param Order\Address $billing
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\ContactDetails
     */
    // phpcs:enable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
    private function getContactDetails(
        Order $order,
        Order\Address $billing
    ) {
        $contactDetails = $this->contactDetailsFactory->create();
        $contactDetails->emailAddress = $this->format->limit($order->getCustomerEmail(), 70);
        $contactDetails->emailMessageType = self::EMAIL_MESSAGE_TYPE;
        $contactDetails->phoneNumber = $billing->getTelephone();
        $contactDetails->faxNumber = $billing->getFax();

        return $contactDetails;
    }

    private function getAccountType(Order $order): string
    {
        return $order->getCustomerIsGuest() ? self::ACCOUNT_TYPE_NONE : self::ACCOUNT_TYPE_EXISTING;
    }
}
