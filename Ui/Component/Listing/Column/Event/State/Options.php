<?php

declare(strict_types=1);

namespace Worldline\Connect\Ui\Component\Listing\Column\Event\State;

use Magento\Framework\Data\OptionSourceInterface;
use Worldline\Connect\Api\Data\EventInterface;

class Options implements OptionSourceInterface
{
    private array $states = [
        EventInterface::STATUS_NEW => 'new',
        EventInterface::STATUS_PROCESSING => 'processing',
        EventInterface::STATUS_SUCCESS => 'success',
        EventInterface::STATUS_FAILED => 'failed',
        EventInterface::STATUS_IGNORED => 'ignored',
    ];

    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->states as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }
}
