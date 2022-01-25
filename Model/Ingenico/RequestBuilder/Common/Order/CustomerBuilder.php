<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order;

use Ingenico\Connect\Helper\Format;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\AccountBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\AddressBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\CompanyInformationBuilder;
use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Customer\DeviceBuilder;
use Ingenico\Connect\Sdk\Domain\Definitions\AddressFactory;
use Ingenico\Connect\Sdk\Domain\Definitions\CompanyInformationFactory;
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

    /**
     * @var CompanyInformationBuilder
     */
    private $companyInformationBuilder;

    /**
     * @var AddressBuilder
     */
    private $addressBuilder;

    /**
     * @var Format
     */
    private $format;

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
        Format $format
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
        $ingenicoCustomer->merchantCustomerId = $this->format->limit(
            $order->getCustomerId() ?: rand(100000, 999999),
            15
        );

        $billing = $order->getBillingAddress();
        if (!empty($billing)) {
            $companyInformation = $this->companyInformationFactory->create();
            $companyInformation->name = $billing->getCompany();
            $ingenicoCustomer->companyInformation = $companyInformation;

            $ingenicoCustomer->contactDetails = $this->getContactDetails($order, $billing);
        }

        $ingenicoCustomer->account = $this->accountBuilder->create($order);
        $ingenicoCustomer->device = $this->deviceBuilder->create($order);
        $ingenicoCustomer->accountType = $this->getAccountType($order);
        $ingenicoCustomer->companyInformation = $this->companyInformationBuilder->create($order);
        $ingenicoCustomer->billingAddress = $this->addressBuilder->create($order);

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
