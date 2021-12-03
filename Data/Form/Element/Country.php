<?php

namespace Ingenico\Connect\Data\Form\Element;

use function array_unshift;

class Country extends \Magento\Directory\Model\Config\Source\Country
{
    /**
     * @inheritdoc
     */
    public function toOptionArray($isMultiselect = false, $foregroundCountries = '')
    {
        $options = parent::toOptionArray($isMultiselect, $foregroundCountries);
        array_unshift($options, ['label' => __('None'), 'value' => '']);
        return $options;
    }
}
