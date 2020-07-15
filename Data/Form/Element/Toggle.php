<?php

declare(strict_types=1);

namespace Ingenico\Connect\Data\Form\Element;

use Ingenico\Connect\Data\Form\Element\Checkbox;

class Toggle extends Checkbox
{
    /**
     * Hide the default checkbox and add toggle class.
     *
     * @return string
     */
    public function getElementHtml(): string
    {
        $this->setData('style', 'position:absolute; clip:rect(0,0,0,0); overflow:hidden');
        $this->addClass('admin__actions-switch-checkbox');

        return '<span style="font-size: 14px">' . parent::getElementHtml() . '</span>';
    }

    /**
     * @return string
     */
    protected function getSecondaryLabelHtml(): string
    {
        $html = '<label for="%s" class="admin__actions-switch-label">
            <span class="admin__actions-switch-text" data-text-on="%s" data-text-off="%s"></span>
        </label>';

        return sprintf(
            $html,
            $this->getHtmlId(),
            $this->getButtonLabel() ? : __('Yes'),
            $this->getButtonLabel() ? : __('No')
        );
    }
}
