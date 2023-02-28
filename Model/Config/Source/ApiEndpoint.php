<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Worldline\Connect\Model\Config;

class ApiEndpoint implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Sandbox'),
                'value' => Config::CONFIG_INGENICO_API_ENDPOINT_SANDBOX,
            ],
            [
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Pre-Production'),
                'value' => Config::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD,
            ],
            [
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Production'),
                'value' => Config::CONFIG_INGENICO_API_ENDPOINT_PROD,
            ],
        ];
    }
}
