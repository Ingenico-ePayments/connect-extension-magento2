<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Refund;

use Ingenico\Connect\Helper\Data as DataHelper;
use Ingenico\Connect\Model\Ingenico\MerchantReference;
use Ingenico\Connect\Sdk\Domain\Definitions\AmountOfMoney;
use Ingenico\Connect\Sdk\Domain\Definitions\ContactDetailsBase;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalName;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundCustomer;
use Ingenico\Connect\Sdk\Domain\Refund\Definitions\RefundReferences;
use Ingenico\Connect\Sdk\Domain\Refund\RefundRequest;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class RefundRequestBuilder
 *
 * @package Ingenico\Connect\Model\Ingenico
 */
class RefundRequestBuilder
{
    const EMAIL_MESSAGE_TYPE = 'html';

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
        RefundRequest $refundRequest,
        MerchantReference $merchantReference
    ) {
        $this->dateTime = $dateTime;
        $this->amountOfMoneyObject = $amountOfMoney;
        $this->refundReferencesObject = $refundReferences;
        $this->personalNameObject = $personalName;
        $this->contactDetailsBaseObject = $contactDetailsBase;
        $this->addressPersonalObject = $addressPersonal;
        $this->refundCustomerObject = $refundCustomer;
        $this->request = $refundRequest;
        $this->merchantReference = $merchantReference;
    }

    public function build(OrderInterface $order, float $amount)
    {
        if ($billing = $order->getBillingAddress()) {
            $this->setCountryCode($billing->getCountryId());
        }

        $this->setAmount(DataHelper::formatIngenicoAmount($amount));
        $this->setCurrencyCode($order->getBaseCurrencyCode());
        $this->setCustomerEmail($order->getCustomerEmail() ?: '');
        $this->setCustomerLastname($order->getCustomerLastname() ?: '');
        $this->setEmailMessageType(self::EMAIL_MESSAGE_TYPE);
        $this->setMerchantReference($this->merchantReference->generateMerchantReferenceForOrder($order));

        return $this->create();
    }

    private function create()
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
    private function setAmount(int $amount)
    {
        $this->amount = $amount;
    }

    /**
     * @param string $countryCode
     */
    private function setCountryCode(string $countryCode)
    {
        $this->countryCode = $countryCode;
    }

    /**
     * @param string $currencyCode
     */
    private function setCurrencyCode(string $currencyCode)
    {
        $this->currencyCode = $currencyCode;
    }

    /**
     * @param string $merchantReference
     */
    private function setMerchantReference(string $merchantReference)
    {
        $this->merchantReference = $merchantReference;
    }

    /**
     * @param string $customerLastname
     */
    private function setCustomerLastname(string $customerLastname)
    {
        $this->customerLastname = $customerLastname;
    }

    /**
     * @param string $customerEmail
     */
    private function setCustomerEmail(string $customerEmail)
    {
        $this->customerEmail = $customerEmail;
    }

    /**
     * @param string $emailMessageType
     */
    private function setEmailMessageType(string $emailMessageType)
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
