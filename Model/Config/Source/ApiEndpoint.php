<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ApiEndpoint implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Sandbox'),
                'value' => 'https://eu.sandbox.api-ingenico.com',
            ],
            [
                'label' => __('Pre-Production'),
                'value' => 'https://world.preprod.api-ingenico.com',
            ],
            [
                'label' => __('Production'),
                'value' => 'https://world.api-ingenico.com',
            ],
        ];
    }
}
