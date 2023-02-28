<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Worldline\Connect\Helper\MetaData;
use Worldline\Connect\Model\System\Message\UpdateAvailable;

use function __;

class Version extends Field
{
    public function __construct(
        private readonly UpdateAvailable $updateAvailable,
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
