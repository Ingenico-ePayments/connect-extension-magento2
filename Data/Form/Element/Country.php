<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Worldline\Connect\Data\Form\Element;

use function __;
use function array_unshift;

// phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFullyQualifiedName
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
