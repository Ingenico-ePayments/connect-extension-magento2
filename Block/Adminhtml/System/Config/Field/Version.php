<?php

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field;

use Ingenico\Connect\Model\System\Message\UpdateAvailable;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Ingenico\Connect\Model\ConfigInterface;

class Version extends Field
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var UpdateAvailable
     */
    private $updateAvailable;

    /**
     * Version constructor.
     *
     * @param UpdateAvailable $updateAvailable
     * @param Context $context
     * @param ConfigInterface $config
     * @param array $data
     */
    public function __construct(
        UpdateAvailable $updateAvailable,
        Context $context,
        ConfigInterface $config,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->updateAvailable = $updateAvailable;
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
        if ($this->updateAvailable->isDisplayed()) {
            $element->setData(
                'comment',
                $this->updateAvailable->getText()
            );
        } else {
            $element->setData(
                'comment',
                __('You currently have the latest version installed.')
            );
        }

        return parent::render($element);
    }
}
