<?php

declare(strict_types=1);

namespace Worldline\Connect\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

use function __;

abstract class TestApiConnection extends Field
{
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _construct()
    {
        parent::_construct();

        $this->setTemplate('Worldline_Connect::system/config/test_api_connection.phtml');
    }

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    protected function _getElementHtml(AbstractElement $element): string
    {
        return $this->_toHtml();
    }

    abstract public function getAjaxUrl(): string;

    abstract public function getId(): string;

    /**
     * @return string
     * @throws LocalizedException
     */
    public function getButtonHtml(): string
    {
        $button = $this->getLayout()->createBlock(Button::class)->setData([
            'id' => $this->getId(),
            'label' => __('Test API Connection'),
        ]);

        return $button->toHtml();
    }
}
