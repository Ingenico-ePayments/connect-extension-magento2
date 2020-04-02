<?php

namespace Ingenico\Connect\Model\Ingenico;

use Ingenico\Connect\Sdk\Domain\Definitions\AmountOfMoney;
use Ingenico\Connect\Sdk\Domain\Definitions\ContactDetailsBase;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalName;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundCustomer;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundReferences;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Class RefundRequestBuilder
 *
 * @package Ingenico\Connect\Model\Ingenico
 * @deprecated use \Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\RefundRequestBuilder instead
 * @see \Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\RefundRequestBuilder
 */
class RefundRequestBuilder
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var AmountOfMoney
     */
    private $amountOfMoneyObject;

    /**
     * @var RefundReferences
     */
    private $refundReferencesObject;

    /**
     * @var PersonalName
     */
    private $personalNameObject;

    /**
     * @var ContactDetailsBase
     */
    private $contactDetailsBaseObject;

    /**
     * @var AddressPersonal
     */
    private $addressPersonalObject;

    /**
     * @var RefundCustomer
     */
    private $refundCustomerObject;

    /**
     * @var RefundRequest
     */
    private $request;

    /**
     * @var int
     */
    private $amount = null;

    /**
     * @var string
     */
    private $countryCode = null;

    /**
     * @var string
     */
    private $currencyCode = null;

    /**
     * @var string
     */
    private $merchantReference = null;

    /**
     * @var string
     */
    private $customerLastname = null;

    /**
     * @var string
     */
    private $customerEmail = null;

    /**
     * @var string
     */
    private $emailMessageType = null;

    /**
     * RefundRequestBuilder constructor.
     *
     * @param DateTime $dateTime
     * @param AmountOfMoney $amountOfMoney
     * @param RefundReferences $refundReferences
     * @param PersonalName $personalName
     * @param ContactDetailsBase $contactDetailsBase
     * @param AddressPersonal $addressPersonal
     * @param RefundCustomer $refundCustomer
     * @param RefundRequest $refundRequest
     */
    public function __construct(
        DateTime $dateTime,
        AmountOfMoney $amountOfMoney,
        RefundReferences $refundReferences,
        PersonalName $personalName,
        ContactDetailsBase $contactDetailsBase,
        AddressPersonal $addressPersonal,
        RefundCustomer $refundCustomer,
        RefundRequest $refundRequest
    ) {
        $this->dateTime = $dateTime;
        $this->amountOfMoneyObject = $amountOfMoney;
        $this->refundReferencesObject = $refundReferences;
        $this->personalNameObject = $personalName;
        $this->contactDetailsBaseObject = $contactDetailsBase;
        $this->addressPersonalObject = $addressPersonal;
        $this->refundCustomerObject = $refundCustomer;
        $this->request = $refundRequest;
    }

    /**
     * @deprecated use \Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund\RefundRequestBuilder::build() instead
     */
    public function create()
    {
        $this->request->refundDate = $this->dateTime->date('Ymd');
        $this->request->refundReferences = $this->buildRefundReferences();
        $this->request->amountOfMoney = $this->buildAmountOfMoney();
        $this->request->customer = $this->buildRefundCustomer();

        return $this->request;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @param string $countryCode
     */
    public function setCountryCode(string $countryCode)
    {
        $this->countryCode = $countryCode;
    }

    /**
     * @param string $currencyCode
     */
    public function setCurrencyCode(string $currencyCode)
    {
        $this->currencyCode = $currencyCode;
    }

    /**
     * @param string $merchantReference
     */
    public function setMerchantReference(string $merchantReference)
    {
        $this->merchantReference = $merchantReference;
    }

    /**
     * @param string $customerLastname
     */
    public function setCustomerLastname(string $customerLastname)
    {
        $this->customerLastname = $customerLastname;
    }

    /**
     * @param string $customerEmail
     */
    public function setCustomerEmail(string $customerEmail)
    {
        $this->customerEmail = $customerEmail;
    }

    /**
     * @param string $emailMessageType
     */
    public function setEmailMessageType(string $emailMessageType)
    {
        $this->emailMessageType = $emailMessageType;
    }

    /**
     * Get money amount
     *
     * @return AmountOfMoney
     */
    private function buildAmountOfMoney()
    {
        $this->amountOfMoneyObject->amount = $this->amount;
        $this->amountOfMoneyObject->currencyCode = $this->currencyCode;

        return $this->amountOfMoneyObject;
    }

    /**
     * @return RefundReferences
     */
    private function buildRefundReferences()
    {
        $this->refundReferencesObject->merchantReference = $this->merchantReference;

        return $this->refundReferencesObject;
    }

    /**
     * @return PersonalName
     */
    private function buildPersonalName()
    {
        $this->personalNameObject->surname = $this->customerLastname;

        return $this->personalNameObject;
    }

    /**
     * @return ContactDetailsBase
     */
    private function buildContactDetailsBase()
    {
        $this->contactDetailsBaseObject->emailAddress = $this->customerEmail;
        $this->contactDetailsBaseObject->emailMessageType = $this->emailMessageType;

        return $this->contactDetailsBaseObject;
    }

    /**
     * @return AddressPersonal
     */
    private function buildAddressPersonal()
    {
        $this->personalNameObject = $this->buildPersonalName();
        $this->addressPersonalObject->name = $this->personalNameObject;
        $this->addressPersonalObject->countryCode = $this->countryCode;

        return $this->addressPersonalObject;
    }

    /**
     * @return RefundCustomer
     */
    private function buildRefundCustomer()
    {
        $this->refundCustomerObject->address = $this->buildAddressPersonal();
        $this->refundCustomerObject->contactDetails = $this->buildContactDetailsBase();

        return $this->refundCustomerObject;
    }
}
