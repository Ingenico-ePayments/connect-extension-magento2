<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field\TechnicalPartner;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Worldline\Connect\Helper\MetaData;

class Link extends Field
{
    public function __construct(
        private readonly MetaData $metaDataHelper,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Shows the extension version
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->setData('target', '_blank');
        $element->setData('href', $this->metaDataHelper->getTechnicalPartnerUrl());
        $element->setData('value', $this->metaDataHelper->getTechnicalPartnerName());

        return parent::render($element);
    }
}
