<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

use function __;

class PaymentProductList extends AbstractFieldArray
{
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _prepareToRender(): void
    {
        $this->addColumn('id', ['label' => __('Identifier')]);
        $this->_addAfter = false;
    }
}
