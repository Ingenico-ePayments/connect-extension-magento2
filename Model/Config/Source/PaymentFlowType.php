<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Config\Source;

use Ingenico\Connect\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

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
                'label' => __('Hosted Payment Page'),
            ],
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_OPTIMIZED_FLOW,
                'label' => __('Payment Optimized Flow'),
            ],
        ];
    }
}
