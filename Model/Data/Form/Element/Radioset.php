<?php
/**
 * See LICENSE.md for license details.
 */
namespace Netresearch\Epayments\Model\Data\Form\Element;

use Magento\Framework\Data\Form\Element\Radios;

/**
 * Class Radioset
 *
 * Implementation of a radio set input element that works inside the Magento system configuration.
 * Used by entering the class name into the "type" attribute of a system.xml field element.
 *
 * @package Netresearch\Epayments\Model
 */
class Radioset extends Radios
{
    /**
     * Add a display none style since the css directive that hides the original input element is missing in
     * system_config.
     *
     * @param mixed $value
     * @return string
     */
    public function getStyle($value)
    {
        return 'display:none';
    }

    /**
     * Returns the current value of the radio group. If no value is selected the first one of all
     * available options will be used.
     *
     * @return mixed
     */
    public function getValue()
    {
        $value = $this->getData('value');

        if ($value === null && !empty($this->getData('values'))) {
            return $this->getData('values')[0]['value'];
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getElementHtml()
    {
        $this->setData('after_element_html', $this->getSecondaryLabelHtml() . $this->getJsHtml());

        return parent::getElementHtml();
    }

    /**
     * Add a hidden input whose value is kept in sync with the checked status of the checkbox.
     *
     * @return string
     */
    private function getJsHtml()
    {
        return <<<HTML
<input type="hidden"
       id="{$this->getHtmlId()}"
       class="{$this->getData('class')}"
       name="{$this->getName()}"
       value="{$this->getValue()}"/>
<script>
    (function() {
        let radios = document.querySelectorAll("input[type='radio'][name='{$this->getName()}']");
        let hidden = document.getElementById("{$this->getId()}");

        for (let i = 0; i < radios.length; i++) {
            if (radios[i].type === "radio") {
                radios[i].name += "[pseudo]";

                // Keep the hidden input value in sync with the radio inputs. We also create a change event for the
                // hidden input because core functionality might listen for it (and the original radio inputs will not
                // report the correct ID).
                //
                // @see module-backend/view/adminhtml/templates/system/shipping/applicable_country.phtml
                radios[i].addEventListener("change", function (event) {
                    event.stopPropagation();
                    hidden.value = event.target.value;

                    let newEvent = document.createEvent("HTMLEvents");
                    newEvent.initEvent("change", false, true);
                    hidden.dispatchEvent(newEvent);
                });
            }
        }
    })();
</script>
HTML;
    }

    /**
     * @return string
     */
    private function getSecondaryLabelHtml()
    {
        $html = '<label for="%s" class="admin__field-label">%s</label>';

        return sprintf(
            $html,
            $this->getHtmlId(),
            $this->getButtonLabel()
        );
    }
}
