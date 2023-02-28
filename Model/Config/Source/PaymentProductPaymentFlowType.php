<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Worldline\Connect\Model\Config;

class PaymentProductPaymentFlowType implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_INLINE,
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Customer enters details in Magento Checkout'),
            ],
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT,
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Customer enters details on hosted page by Worldline'),
            ],
        ];
    }
}
