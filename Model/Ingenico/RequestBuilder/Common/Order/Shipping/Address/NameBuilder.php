<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Shipping\Address;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalName;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalNameFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;

class NameBuilder
{
    /**
     * @var PersonalNameFactory
     */
    private $personalNameFactory;

    public function __construct(PersonalNameFactory $personalNameFactory)
    {
        $this->personalNameFactory = $personalNameFactory;
    }

    public function create(OrderAddressInterface $address): PersonalName
    {
        $personalName = $this->personalNameFactory->create();
        $personalName->firstName = $address->getFirstname();
        $personalName->surname = $address->getLastname();
        $personalName->surnamePrefix = $address->getMiddlename();
        $personalName->title = $address->getPrefix();
        return $personalName;
    }
}
