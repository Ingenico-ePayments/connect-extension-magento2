<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order\Shipping\Address;

use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalName;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\PersonalNameFactory;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Worldline\Connect\Helper\Format;

class NameBuilder
{
    public function __construct(
        private readonly PersonalNameFactory $personalNameFactory,
        private readonly Format $format
    ) {
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
