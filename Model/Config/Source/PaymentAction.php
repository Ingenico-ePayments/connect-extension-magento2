<?php

declare(strict_types=1);

namespace Ingenico\Connect\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\MethodInterface;

class PaymentAction implements ArrayInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => MethodInterface::ACTION_AUTHORIZE,
                'label' => __('Delayed Capture'),
            ],
            [
                'value' => MethodInterface::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Direct Capture'),
            ]
        ];
    }
}
