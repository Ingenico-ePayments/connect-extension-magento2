<?php

namespace Netresearch\Epayments\Model\Ingenico\RequestBuilder\ProductSpecificInput;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Hostedcheckout\CreateHostedCheckoutRequest;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\CreateMandateBase;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\CreateMandateBaseFactory;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\MandateAddress;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\MandateAddressFactory;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\MandateCustomer;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\MandateCustomerFactory;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\MandatePersonalInformation;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\MandatePersonalInformationFactory;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\MandatePersonalName;
use Ingenico\Connect\Sdk\Domain\Mandates\Definitions\MandatePersonalNameFactory;
use Ingenico\Connect\Sdk\Domain\Payment\CreatePaymentRequest;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\SepaDirectDebitPaymentProduct771SpecificInputBase;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\SepaDirectDebitPaymentProduct771SpecificInputBaseFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Netresearch\Epayments\Model\Ingenico\RequestBuilder\DecoratorInterface;

class Product771Decorator implements DecoratorInterface
{
    /**
     * @var SepaDirectDebitPaymentProduct771SpecificInputBaseFactory
     */
    private $inputFactory;

    /**
     * @var MandateCustomerFactory
     */
    private $mandateCustomerFactory;

    /**
     * @var MandatePersonalInformationFactory
     */
    private $mandatePersonalInfoFactory;

    /**
     * @var MandateAddressFactory
     */
    private $mandateAddressFactory;

    /** @var CreateMandateBaseFactory */
    private $createMandateBaseFactory;

    /**
     * @var MandatePersonalNameFactory
     */
    private $mandatePersonalNameFactory;

    /**
     * Product771Decorator constructor.
     * @param SepaDirectDebitPaymentProduct771SpecificInputBaseFactory $inputFactory
     * @param MandateCustomerFactory $mandateCustomerFactory
     * @param MandatePersonalInformationFactory $mandatePersonalInfoFactory
     * @param MandateAddressFactory $mandateAddressFactory
     * @param CreateMandateBaseFactory $createMandateBaseFactory
     * @param MandatePersonalNameFactory $mandatePersonalNameFactory
     */
    public function __construct(
        SepaDirectDebitPaymentProduct771SpecificInputBaseFactory $inputFactory,
        MandateCustomerFactory $mandateCustomerFactory,
        MandatePersonalInformationFactory $mandatePersonalInfoFactory,
        MandateAddressFactory $mandateAddressFactory,
        CreateMandateBaseFactory $createMandateBaseFactory,
        MandatePersonalNameFactory $mandatePersonalNameFactory
    ) {
        $this->inputFactory = $inputFactory;
        $this->mandateCustomerFactory = $mandateCustomerFactory;
        $this->mandatePersonalInfoFactory = $mandatePersonalInfoFactory;
        $this->mandateAddressFactory = $mandateAddressFactory;
        $this->createMandateBaseFactory = $createMandateBaseFactory;
        $this->mandatePersonalNameFactory = $mandatePersonalNameFactory;
    }

    /**
     * @param DataObject $request
     * @param OrderInterface $order
     * @return DataObject|CreateHostedCheckoutRequest|CreatePaymentRequest
     */
    public function decorate(DataObject $request, OrderInterface $order)
    {
        /** @var CreatePaymentRequest|CreateHostedCheckoutRequest $request */
        /** @var SepaDirectDebitPaymentProduct771SpecificInputBase $input */
        $input = $this->inputFactory->create();
        $input->mandate = $this->generateMandate($order);
        $request->sepaDirectDebitPaymentMethodSpecificInput->paymentProduct771SpecificInput = $input;
        return $request;
    }

    /**
     * @param OrderInterface $order
     * @return CreateMandateBase
     */
    private function generateMandate(OrderInterface $order)
    {
        /** @var CreateMandateBase $mandate */
        $mandate = $this->createMandateBaseFactory->create();
        $mandate->customer = $this->generateCustomer($order);
        $mandate->customerReference = $order->getCustomerId() ?: $order->getCustomerEmail();
        $mandate->recurrenceType = 'UNIQUE';
        $mandate->signatureType = 'UNSIGNED';
        return $mandate;
    }

    /**
     * @param OrderInterface $order
     * @return MandateCustomer
     */
    private function generateCustomer(OrderInterface $order)
    {
        /** @var MandateCustomer $customer */
        $customer = $this->mandateCustomerFactory->create();
        if ($order->getBillingAddress()->getCompany() !== null) {
            $customer->companyName = $order->getBillingAddress()->getCompany();
        }
        $customer->mandateAddress = $this->generateAddress($order);
        $customer->personalInformation = $this->generatePersonalInfo($order);
        return $customer;
    }

    /**
     * @param OrderInterface $order
     * @return MandateAddress
     */
    private function generateAddress(OrderInterface $order)
    {
        /** @var MandateAddress $address */
        $address = $this->mandateAddressFactory->create();
        $address->city = $order->getBillingAddress()->getCity();
        $address->countryCode = $order->getBillingAddress()->getCountryId();
        $address->street = mb_substr(implode(" ", $order->getBillingAddress()->getStreet()), 0, 50);
        $address->zip = $order->getBillingAddress()->getPostcode();
        return $address;
    }

    /**
     * @param OrderInterface $order
     * @return MandatePersonalInformation
     */
    private function generatePersonalInfo(OrderInterface $order)
    {
        /** @var MandatePersonalInformation $personalInformation */
        $personalInformation = $this->mandatePersonalInfoFactory->create();
        /** @var MandatePersonalName $name */
        $name = $this->mandatePersonalNameFactory->create();
        $name->firstName = $order->getBillingAddress()->getFirstname();
        $name->surname = $order->getBillingAddress()->getLastname();
        $personalInformation->title = $order->getBillingAddress()->getPrefix() ?: 'Mr.';
        return $personalInformation;
    }
}
