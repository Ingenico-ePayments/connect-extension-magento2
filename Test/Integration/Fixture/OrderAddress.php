<?php

namespace Ingenico\Connect\Test\Integration\Fixture;

use Magento\Sales\Api\Data\OrderAddressInterface;

class OrderAddress
{
    const BILLING_ADDRESS_DATA = [
        OrderAddressInterface::PREFIX => 'Mr.',
        OrderAddressInterface::FIRSTNAME => 'Integration',
        OrderAddressInterface::MIDDLENAME => 'von der',
        OrderAddressInterface::LASTNAME => 'Test',
        OrderAddressInterface::STREET => "Galaxy Lane\n42\nbis",
        OrderAddressInterface::POSTCODE => '90210',
        OrderAddressInterface::CITY => 'Lexington',
        OrderAddressInterface::COUNTRY_ID => 'US',
        OrderAddressInterface::REGION => 'Alabama',
        OrderAddressInterface::REGION_ID => 1,  // Magento 2 Default
        OrderAddressInterface::EMAIL => 'foo@example.com',
        OrderAddressInterface::TELEPHONE => '+1 800 555 0123',
        OrderAddressInterface::VAT_ID => 'US123456789',
        OrderAddressInterface::COMPANY => 'A.C.M.E. inc.',
    ];

    const SHIPPING_ADDRESS_DATA = [
        OrderAddressInterface::PREFIX => 'Ms.',
        OrderAddressInterface::FIRSTNAME => 'Foo',
        OrderAddressInterface::MIDDLENAME => 'Roxy',
        OrderAddressInterface::LASTNAME => 'Testing',
        OrderAddressInterface::STREET => "Sol\n1\nalpha",
        OrderAddressInterface::POSTCODE => '42',
        OrderAddressInterface::CITY => 'Eindhoven',
        OrderAddressInterface::COUNTRY_ID => 'US',
        OrderAddressInterface::REGION => 'Alaska',
        OrderAddressInterface::REGION_ID => 2, // Magento 2 Default
        OrderAddressInterface::EMAIL => 'bar@example.com',
        OrderAddressInterface::TELEPHONE => '+31 (0)40 1234 567',
        OrderAddressInterface::VAT_ID => 'EU123456789',
        OrderAddressInterface::COMPANY => 'Magento inc.',
    ];

    // For testing purposes:
    const BILLING_REGION_CODE = 'AL';
    const SHIPPING_REGION_CODE = 'AK';
    const SHIPPING_STREET = 'Sol';
    const SHIPPING_HOUSE_NUMBER = '1';
    const SHIPPING_ADDITIONAL_INFO = 'alpha';
}
