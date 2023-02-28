<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field\Documentation;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Link extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->setData('target', '_blank');
        $element->setData('href', 'https://epayments.developer-ingenico.com/');
        $element->setData('value', 'https://epayments.developer-ingenico.com/');

        return parent::render($element);
    }
}
