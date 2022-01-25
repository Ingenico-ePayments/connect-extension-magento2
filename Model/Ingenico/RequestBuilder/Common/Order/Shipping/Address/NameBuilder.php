<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Shipping\Address;

use Ingenico\Connect\Helper\Format;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalName;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalNameFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;

class NameBuilder
{
    /**
     * @var PersonalNameFactory
     */
    private $personalNameFactory;

    /**
     * @var Format
     */
    private $format;

    public function __construct(PersonalNameFactory $personalNameFactory, Format $format)
    {
        $this->personalNameFactory = $personalNameFactory;
        $this->format = $format;
    }

    public function create(OrderAddressInterface $address): PersonalName
    {
        $personalName = $this->personalNameFactory->create();
        $personalName->firstName = $this->format->limit($address->getFirstname(), 15);
        $personalName->surname = $this->format->limit($address->getLastname(), 70);
        $personalName->surnamePrefix = $address->getMiddlename();
        $personalName->title = $address->getPrefix();
        return $personalName;
    }
}
