<?php

declare(strict_types=1);

namespace Ingenico\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Backend\Block\Widget\Button;
use Magento\Framework\Exception\LocalizedException;

class TestApiConnection extends Field
{
    /** @var string */
    protected $_template = 'Ingenico_Connect::system/config/test_api_connection.phtml';

    /**
     * @param  AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    public function getAjaxUrl(): string
    {
        return $this->getUrl('epayments/Api/TestConnection');
    }

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(
            Button::class
        )->setData(
            [
                'id' => 'test_api_connection_button',
                'label' => __('Test API Connection'),
            ]
        );

        return $button->toHtml();
    }
}
