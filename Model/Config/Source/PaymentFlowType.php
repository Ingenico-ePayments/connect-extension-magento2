<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Worldline\Connect\Model\Config;

class PaymentFlowType implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_HOSTED_CHECKOUT,
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Hosted Checkout'),
            ],
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_OPTIMIZED_FLOW,
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Inline'),
            ],
        ];
    }
}
