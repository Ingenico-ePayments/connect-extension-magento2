<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\MethodInterface;

use function __;

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
            ],
        ];
    }
}
