<?php

declare(strict_types=1);

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field\Merchant;

use Ingenico\Connect\Helper\GitHub;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Link extends Field
{
    /** @var GitHub */
    protected $gitHubHelper;
    
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
        $element->setData('href', 'mailto:merchantservices@ingenico.com');
        $element->setData('value', 'Ingenico ePayments - Merchant Services');
        
        return parent::render($element);
    }
}
