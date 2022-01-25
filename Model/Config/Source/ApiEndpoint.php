<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Config\Source;

use Ingenico\Connect\Model\Config;
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
                'value' => Config::CONFIG_INGENICO_API_ENDPOINT_SANDBOX,
            ],
            [
                'label' => __('Pre-Production'),
                'value' => Config::CONFIG_INGENICO_API_ENDPOINT_PRE_PROD,
            ],
            [
                'label' => __('Production'),
                'value' => Config::CONFIG_INGENICO_API_ENDPOINT_PROD,
            ],
        ];
    }
}
