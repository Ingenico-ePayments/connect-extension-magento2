<?php

namespace Netresearch\Epayments\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Netresearch\Epayments\Model\ConfigInterface;

class Version extends Field
{
    /** @var ConfigInterface */
    private $config;

    /**
     * @param Context $context
     * @param ConfigInterface $config
     * @param array $data
     */
    public function __construct(Context $context, ConfigInterface $config, array $data = [])
    {
        parent::__construct($context, $data);
        $this->config = $config;
    }

    /**
     * Shows the extension version
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->setValue($this->config->getVersion());

        return parent::render($element);
    }
}
