<?php
/**
 * See LICENSE.md for license details.
 */
namespace Netresearch\Epayments\Model\Data\Form\Element;

use Magento\Framework\Data\Form\Element\Checkboxes;

/**
 * Class Checkbox
 *
 * Implementation of a checkbox set input element that works inside the Magento system configuration and mimics a
 * multiselect, concatenating the values of all selected options separated with a comma inside a hidden input.
 * Used by entering the class name into the "type" attribute of a system.xml field element.
 *
 * @package Netresearch\Epayments\Model
 */
class Checkboxset extends Checkboxes
{
    const PSEUDO_POSTFIX = '_hidden'; // used to create the hidden input id.

    /**
     * @return string
     */
    public function getElementHtml()
    {
        $this->setData('value', $this->filterUnavailableValues());
        $this->setData('after_element_html', $this->getAfterHtml());

        return parent::getElementHtml();
    }

    /**
     * Add a hidden input whose value is kept in sync with the checked status of the checkboxes
     *
     * @return string
     */
    private function getAfterHtml()
    {
        $html = '<input type="hidden" id="%s" value="%s"/>
        <script>
            (function() {
                let checkboxes = document.querySelectorAll("[name=\'%s\']");
                let hidden = document.getElementById("%s");
                /** Make the hidden input the submitted one. **/
                hidden.name = checkboxes.item(0).name;

                for (let i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].name = "";
                    let values = hidden.value.split(",");
                    if (values.indexOf(checkboxes[i].value) !== -1) {
                        checkboxes[i].checked = true;
                    }
                    /** keep the hidden input value in sync with the checkboxes. **/
                    checkboxes[i].addEventListener("change", function (event) {
                        let checkbox = event.target;
                        let values = hidden.value.split(",");
                        var valueAlreadyIncluded = values.indexOf(checkbox.value) !== -1; 
                        if (checkbox.checked && !valueAlreadyIncluded) {
                            values.push(checkbox.value);
                        } else if (!checkbox.checked && valueAlreadyIncluded) {
                            values.splice(values.indexOf(checkbox.value), 1)
                        }
                        hidden.value = values.filter(Boolean).join();
                    });
                };
            })();
        </script>';

        return sprintf(
            $html,
            $this->getHtmlId() . self::PSEUDO_POSTFIX,
            $this->getData('value'),
            $this->getName(),
            $this->getHtmlId() . self::PSEUDO_POSTFIX
        );
    }

    /**
     * Remove previously selected values whose option is not available any more.
     *
     * @return string
     */
    private function filterUnavailableValues()
    {
        $values = explode(',', $this->getData('value'));
        $availableValues = array_map(
            function ($value) {
                return $value['value'];
            },
            $this->getData('values')
        );

        return implode(',', array_intersect($values, $availableValues));
    }
}
