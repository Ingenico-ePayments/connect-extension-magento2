<?php

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field\GitHub;

use Ingenico\Connect\Helper\GitHub;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Link extends Field
{
    /** @var GitHub */
    protected $gitHubHelper;
    
    /**
     * @param Context $context
     * @param GitHub $gitHubHelper
     * @param array $data
     */
    public function __construct(Context $context, GitHub $gitHubHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->gitHubHelper = $gitHubHelper;
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
        $element->setData('href', $this->gitHubHelper->getRepositoryUrl());
        $element->setData('value', $this->gitHubHelper->getRepositoryUrl());

        return parent::render($element);
    }
}
