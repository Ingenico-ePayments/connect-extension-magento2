<?php

declare(strict_types=1);

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field\TechnicalPartner;

use Ingenico\Connect\Helper\MetaData;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Link extends Field
{
    /** @var MetaData */
    private $metaDataHelper;
    
    public function __construct(Context $context, MetaData $metaDataHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->metaDataHelper = $metaDataHelper;
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
