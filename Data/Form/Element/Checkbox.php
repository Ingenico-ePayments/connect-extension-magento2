<?php

declare(strict_types=1);

namespace Worldline\Connect\Data\Form\Element;

use Magento\Framework\Data\Form\Element\Checkbox as CoreCheckbox;

class Checkbox extends CoreCheckbox
{
    public const PSEUDO_POSTFIX = '_pseudo'; // used to create the hidden input id.

    /**
     * @return string
     */
    public function getElementHtml(): string
    {
        $this->setIsChecked((bool) $this->getData('value'));
        $this->setData('after_element_js', $this->getSecondaryLabelHtml() . $this->getJsHtml());

        return '<span style="font-size: 14px">' . parent::getElementHtml() . '</span>';
    }

    /**
     * @return string
     */
    public function getButtonLabel(): string
    {
        return $this->getData('field_config')['button_label'] ?? '';
    }

    /**
     * Add a hidden input whose value is kept in sync with the checked status of the checkbox.
     *
     * @return string
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.FunctionLength.FunctionLength
    protected function getJsHtml(): string
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

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
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
    protected function getSecondaryLabelHtml(): string
    {
        $html = '<label for="%s" class="admin__field-label">%s</label>';

        // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
        return sprintf(
            $html,
            $this->getHtmlId(),
            // phpcs:ignore SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly.ReferenceViaFallbackGlobalName
            __($this->getButtonLabel())
        );
    }
}
