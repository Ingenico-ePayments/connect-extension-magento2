<?php

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field;

use Ingenico\Connect\Helper\MetaData;
use Ingenico\Connect\Model\System\Message\UpdateAvailable;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class Version extends Field
{
    /**
     * @var UpdateAvailable
     */
    private $updateAvailable;
    
    /** @var MetaData */
    private $metaDataHelper;
    
    public function __construct(
        UpdateAvailable $updateAvailable,
        Context $context,
        MetaData $metaDataHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->updateAvailable = $updateAvailable;
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
        $element->setValue($this->metaDataHelper->getModuleVersion());
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
