<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\AccountBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\DeviceBuilder;
use Ingenico\Connect\Sdk\Domain\Definitions\AddressFactory;
use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformationFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonalFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ContactDetailsFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\CustomerFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalInformationFactory;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalNameFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;

/**
 * Class CustomerBuilder
 */
class CustomerBuilder
{
    const EMAIL_MESSAGE_TYPE = 'html';
    const GENDER_MALE = 0;
    const GENDER_FEMALE = 1;
    const ACCOUNT_TYPE_NONE = 'none';
    const ACCOUNT_TYPE_EXISTING = 'existing';

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var PersonalInformationFactory
     */
    private $personalInformationFactory;

    /**
     * @var CompanyInformationFactory
     */
    private $companyInformationFactory;

    /**
     * @var ContactDetailsFactory
     */
    private $contactDetailsFactory;

    /**
     * @var PersonalNameFactory
     */
    private $personalNameFactory;

    /**
     * @var AddressPersonalFactory
     */
    private $addressPersonalFactory;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var AccountBuilder
     */
    private $accountBuilder;

    /**
     * @var DeviceBuilder
     */
    private $deviceBuilder;

    public function __construct(
        CustomerFactory $customerFactory,
        PersonalInformationFactory $personalInformationFactory,
        CompanyInformationFactory $companyInformationFactory,
        ContactDetailsFactory $contactDetailsFactory,
        PersonalNameFactory $personalNameFactory,
        AddressPersonalFactory $addressPersonalFactory,
        AddressFactory $addressFactory,
        AccountBuilder $accountBuilder,
        DeviceBuilder $deviceBuilder,
        TimezoneInterface $timezone
    ) {
        $this->customerFactory = $customerFactory;
        $this->personalInformationFactory = $personalInformationFactory;
        $this->companyInformationFactory = $companyInformationFactory;
        $this->contactDetailsFactory = $contactDetailsFactory;
        $this->personalNameFactory = $personalNameFactory;
        $this->addressPersonalFactory = $addressPersonalFactory;
        $this->addressFactory = $addressFactory;
        $this->accountBuilder = $accountBuilder;
        $this->deviceBuilder = $deviceBuilder;
        $this->timezone = $timezone;
    }

    /**
     * @param OrderInterface $order
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\Customer
     */
    public function create(OrderInterface $order)
    {
        $ingenicoCustomer = $this->customerFactory->create();

        $ingenicoCustomer->personalInformation = $this->getPersonalInformation($order);
        // create dummy customer id
        $ingenicoCustomer->merchantCustomerId = $order->getCustomerId() ?: rand(100000, 999999);

        $billing = $order->getBillingAddress();
        if (!empty($billing)) {
            $ingenicoCustomer->vatNumber = $billing->getVatId();

            $companyInformation = $this->companyInformationFactory->create();
            $companyInformation->name = $billing->getCompany();
            $ingenicoCustomer->companyInformation = $companyInformation;

            $ingenicoCustomer->billingAddress = $this->getBillingAddress($billing);
            $ingenicoCustomer->contactDetails = $this->getContactDetails($order, $billing);
        }

        $shipping = $order->getShippingAddress();
        if (!empty($shipping)) {
            $ingenicoCustomer->shippingAddress = $this->getAddressPersonal($shipping);
        }

        $ingenicoCustomer->account = $this->accountBuilder->create($order);
        $ingenicoCustomer->device = $this->deviceBuilder->create();
        $ingenicoCustomer->accountType = $this->getAccountType($order);

        return $ingenicoCustomer;
    }

    /**
     * @param Order $order
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalInformation
     */
    private function getPersonalInformation(OrderInterface $order)
    {
        $personalInformation = $this->personalInformationFactory->create();

        $personalName = $this->personalNameFactory->create();
        $personalName->title = $order->getCustomerPrefix();
        $personalName->firstName = $order->getCustomerFirstname();
        $personalName->surnamePrefix = $order->getCustomerMiddlename();
        $personalName->surname = $order->getCustomerLastname();

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

    /**
     * @param Order\Address $billing
     * @return \Ingenico\Connect\Sdk\Domain\Definitions\Address
     */
    private function getBillingAddress(Order\Address $billing)
    {
        $billingAddress = $this->addressFactory->create();
        /** @var array $streetArray */
        $streetArray = $billing->getStreet();
        $billingAddress->street = array_shift($streetArray);
        if (!empty($streetArray)) {
            $billingAddress->additionalInfo = implode(', ', $streetArray);
        }
        $billingAddress->zip = $billing->getPostcode();
        $billingAddress->city = $billing->getCity();
        $billingAddress->state = $billing->getRegion();
        $billingAddress->countryCode = $billing->getCountryId();

        return $billingAddress;
    }

    /**
     * @param Order $order
     * @param Order\Address $billing
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\ContactDetails
     */
    private function getContactDetails(
        Order $order,
        Order\Address $billing
    ) {
        $contactDetails = $this->contactDetailsFactory->create();
        $contactDetails->emailAddress = $order->getCustomerEmail();
        $contactDetails->emailMessageType = self::EMAIL_MESSAGE_TYPE;
        $contactDetails->phoneNumber = $billing->getTelephone();
        $contactDetails->faxNumber = $billing->getFax();

        return $contactDetails;
    }

    /**
     * @param Order\Address $shipping
     * @param Order\Address $billing
     * @return \Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal
     */
    private function getAddressPersonal(
        Order\Address $shipping
    ) {
        $shippingName = $this->personalNameFactory->create();
        $shippingName->title = $shipping->getPrefix();
        $shippingName->firstName = $shipping->getFirstname();
        $shippingName->surname = $shipping->getLastname();

        $shippingAddress = $this->addressPersonalFactory->create();
        $shippingAddress->name = $shippingName;
        /** @var array $streetArray */
        $streetArray = $shipping->getStreet();
        $shippingAddress->street = array_shift($streetArray);
        if (!empty($streetArray)) {
            $shippingAddress->additionalInfo = implode(', ', $streetArray);
        }
        $shippingAddress->zip = $shipping->getPostcode();
        $shippingAddress->city = $shipping->getCity();
        $shippingAddress->state = $shipping->getRegion();
        $shippingAddress->countryCode = $shipping->getCountryId();

        return $shippingAddress;
    }

    private function getAccountType(Order $order): string
    {
        return $order->getCustomerIsGuest() ? self::ACCOUNT_TYPE_NONE : self::ACCOUNT_TYPE_EXISTING;
    }
}
