<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Config\Source;

use Ingenico\Connect\Model\Config;
use Magento\Framework\Data\OptionSourceInterface;

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
                'label' => __('Customer enters details in Magento Checkout'),
            ],
            [
                'value' => Config::CONFIG_INGENICO_CHECKOUT_TYPE_REDIRECT,
                'label' => __('Customer enters details on hosted page by Ingenico'),
            ],
        ];
    }
}
