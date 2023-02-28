<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Model\Worldline\RequestBuilder\Common\Order;

use Ingenico\Connect\Sdk\DataObject;
use Ingenico\Connect\Sdk\Domain\Definitions\Address;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\AddressPersonal;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Worldline\Connect\Helper\Format;

abstract class AbstractAddressBuilder
{
    public const ADDITIONAL_INFO = 'additional_info';
    public const HOUSE_NUMBER = 'house_number';
    public const STREET = 'street';

    /**
     * @var Format
     */
    // phpcs:ignore SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
    private $format;

    /**
     * @param Format $config
     */
    public function __construct(Format $format)
    {
        $this->format = $format;
    }

    /**
     * @param DataObject|Address|AddressPersonal $dataObject
     * @param OrderAddressInterface $orderAddress
     * @throws LocalizedException
     */
    protected function populateAddress(DataObject $dataObject, OrderAddressInterface $orderAddress)
    {
        if (!($dataObject instanceof Address || $dataObject instanceof AddressPersonal)) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            throw new LocalizedException(__('Data Objects needs to be an instance of Address or AddressPersonal'));
        }

        $dataObject->city = $this->format->limit($orderAddress->getCity(), 40);
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
            $dataObject->street = $this->format->limit($addressArray[self::STREET], 50);
            $dataObject->houseNumber = $this->format->limit($addressArray[self::HOUSE_NUMBER], 15);
            $dataObject->additionalInfo = $this->format->limit($addressArray[self::ADDITIONAL_INFO], 50);
        }
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingAnyTypeHint
    private function getHouseNumberFromAddress(array $streetLines)
    {
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $address = trim(implode(' ', $streetLines));
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $address = str_replace(['nº'], '', $address);

        // We reverse the address and the regex, so we start searching for the suffix, then the house number.
        // The remainder is considered to be the street name.
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $address = strrev($address);
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        $hasMatch = preg_match('/^([a-zA-Z\-\s]*)\s*?(\d+)\s+(.*)$/', $address, $match);

        if (!$hasMatch) {
            return [
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                self::STREET => strrev($address),
                self::HOUSE_NUMBER => '',
                self::ADDITIONAL_INFO => '',
            ];
        }

        return [
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            self::STREET => strrev(trim($match[3], ' ,-')),
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            self::HOUSE_NUMBER => strrev(trim($match[2])),
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        if (preg_match('/^(?P<firstCode>[A-Z]{1,3})(-(?P<secondCode>[A-Z0-9]{1,3}))?$/i', $regionCode, $matches)) {
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            if (array_key_exists('secondCode', $matches)) {
                $countryId = $orderAddress->getCountryId();
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                return strcasecmp((string) $countryId, $matches['firstCode']) === 0 ? $matches['secondCode'] : null;
            }
            return $matches['firstCode'];
        }

        return null;
    }
}
