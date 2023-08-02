<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Config\Source\GooglePay;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            [
                'value' => 'TEST',
                'label' => 'Test',
            ],
            [
                'value' => 'PRODUCTION',
                'label' => 'Production',
            ],
        ];
    }
}
