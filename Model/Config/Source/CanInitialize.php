<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class CanInitialize implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => '0',
                'label' => __('Inline'),
            ],
            [
                'value' => '1',
                'label' => __('Hosted'),
            ]
        ];
    }
}
