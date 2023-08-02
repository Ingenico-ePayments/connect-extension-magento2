<?php

declare(strict_types=1);

namespace Worldline\Connect\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OptionSource implements OptionSourceInterface
{
    private array $options;

    public function __construct(array $options)
    {
        foreach ($options as $value => $label) {
            $this->options[] = ['value' => $value, 'label' => $label];
        }
    }

    public function toOptionArray(): array
    {
        return $this->options;
    }
}
