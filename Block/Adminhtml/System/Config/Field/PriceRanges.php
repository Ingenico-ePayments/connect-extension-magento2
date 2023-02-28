<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

use function __;

class PriceRanges extends AbstractFieldArray
{
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _prepareToRender()
    {
        $this->addColumn('currency', ['label' => __('Currency'), 'class' => 'required-entry']);
        $this->addColumn('minimum', ['label' => __('Minimum'), 'class' => 'validate-greater-than-zero']);
        $this->addColumn('maximum', ['label' => __('Maximum'), 'class' => 'validate-greater-than-zero']);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }
}
