<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CanInitialize implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => '0',
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Inline'),
            ],
            [
                'value' => '1',
                // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
                'label' => __('Hosted'),
            // phpcs:ignore SlevomatCodingStandard.Arrays.TrailingArrayComma.MissingTrailingComma
            ]
        ];
    }
}
