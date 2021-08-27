<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Definitions\Address;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderAddressInterface;

abstract class AbstractAddressBuilder
{
    const ADDITIONAL_INFO = 'additional_info';
    const HOUSE_NUMBER = 'house_number';
    const STREET = 'street';

    /**
     * @param DataObject|Address|AddressPersonal $dataObject
     * @param OrderAddressInterface $orderAddress
     * @throws LocalizedException
     */
    protected function populateAddress(DataObject $dataObject, OrderAddressInterface $orderAddress)
    {
        if (!($dataObject instanceof Address || $dataObject instanceof AddressPersonal)) {
            throw new LocalizedException(__('Data Objects needs to be an instance of Address or AddressPersonal'));
        }

        $dataObject->city = $orderAddress->getCity();
        $dataObject->countryCode = $orderAddress->getCountryId();
        $dataObject->state = $orderAddress->getRegion();
        $regionCode = $this->getRegionCodeFromOrderAddress($orderAddress);
        if ($regionCode !== null) {
            $dataObject->stateCode = $regionCode;
        }
        $dataObject->zip = $orderAddress->getPostcode();
        $street = $orderAddress->getStreet();
        if ($street !== null) {
            $addressArray = $this->getHouseNumberFromAddress($street);
            $dataObject->street = $addressArray[self::STREET];
            $dataObject->houseNumber = $addressArray[self::HOUSE_NUMBER];
            $dataObject->additionalInfo = $addressArray[self::ADDITIONAL_INFO];
        }
    }

    private function getHouseNumberFromAddress(array $streetLines)
    {
        $address = trim(implode(' ', $streetLines));
        $address = str_replace(['nº'], '', $address);

        // We reverse the address and the regex, so we start searching for the suffix, then the house number.
        // The remainder is considered to be the street name.
        $address = strrev($address);
        $hasMatch = preg_match('/^([a-zA-Z\-\s]*)\s*?(\d+)\s+(.*)$/', $address, $match);

        if (!$hasMatch) {
            return [
                self::STREET => strrev($address),
                self::HOUSE_NUMBER => '',
                self::ADDITIONAL_INFO => '',
            ];
        }

        return [
            self::STREET => strrev(trim($match[3], ' ,-')),
            self::HOUSE_NUMBER => strrev(trim($match[2])),
            self::ADDITIONAL_INFO => strrev(trim($match[1], ' ,-')),
        ];
    }

    private function getRegionCodeFromOrderAddress(OrderAddressInterface $orderAddress): ?string
    {
        $regionCode = $orderAddress->getRegionCode();
        if ($regionCode === null) {
            return null;
        }

        // Check if the region code has the ISO-3166 format: ABC(-ABC), where the last part is optional.
        if (preg_match('/^(?P<firstCode>[A-Z]{1,3})(-(?P<secondCode>[A-Z0-9]{1,3}))?$/i', $regionCode, $matches)) {
            if (array_key_exists('secondCode', $matches)) {
                $countryId = $orderAddress->getCountryId();
                return strcasecmp((string) $countryId, $matches['firstCode']) === 0 ? $matches['secondCode'] : null;
            }
            return $matches['firstCode'];
        }

        return null;
    }
}
