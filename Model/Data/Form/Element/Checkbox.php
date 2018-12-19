<?php
/**
 * See LICENSE.md for license details.
 */
namespace Netresearch\Epayments\Model\Data\Form\Element;

use Magento\Framework\Data\Form\Element\Checkbox as CoreCheckbox;

/**
 * Class Checkbox
 *
 * Implementation of a checkbox boolean input element that works inside the Magento system configuration.
 * Used by entering the class name into the "type" attribute of a system.xml field element.
 *
 * @package Netresearch\Epayments\Model
 */
class Checkbox extends CoreCheckbox
{
    const PSEUDO_POSTFIX = '_pseudo'; // used to create the hidden input id.

    /**
     * @return string
     */
    public function getElementHtml()
    {
        $this->setIsChecked((bool)$this->getData('value'));
        $this->setData('after_element_js', $this->getSecondaryLabelHtml() . $this->getJsHtml());

        return parent::getElementHtml();
    }

    /**
     * @return string
     */
    public function getButtonLabel()
    {
        return isset($this->getData('field_config')['button_label'])
            ? $this->getData('field_config')['button_label']
            : '';
    }

    /**
     * Add a hidden input whose value is kept in sync with the checked status of the checkbox.
     *
     * @return string
     */
    protected function getJsHtml()
    {
        $html = '<input type="hidden" id="%s" value="%s"/>
        <script>
            (function() {
                let checkbox = document.getElementById("%s");
                let hidden = document.getElementById("%s");
                /** Make the hidden input the submitted one. **/
                hidden.name = checkbox.name;
                checkbox.name = "";
                /**
                 * keep the hidden input value in sync with the checkbox. We also update the checkbox value because
                 * it may be needed by the core.
                 * 
                 * @see module-backend/view/adminhtml/templates/system/shipping/applicable_country.phtml
                 **/
                checkbox.addEventListener("change", function (event) {
                    checkbox.value = hidden.value = event.target.checked ? "1" : "0";
                });    
            })();   
        </script>';

        return sprintf(
            $html,
            $this->getHtmlId() . self::PSEUDO_POSTFIX,
            $this->getIsChecked() ? '1' : '0',
            $this->getHtmlId(),
            $this->getHtmlId() . self::PSEUDO_POSTFIX
        );
    }

    /**
     * @return string
     */
    protected function getSecondaryLabelHtml()
    {
        $html = '<label for="%s" class="admin__field-label">%s</label>';

        return sprintf(
            $html,
            $this->getHtmlId(),
            $this->getButtonLabel()
        );
    }
}
