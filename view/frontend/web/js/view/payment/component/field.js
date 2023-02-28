define([
    'underscore',
    'Magento_Ui/js/form/element/abstract',
    'mageUtils',
], function (_, Abstract, utils) {
    'use strict';

    return Abstract.extend({

        defaults: {
            value: '',
            provider: {}, // workaround for missing data provider.
            autocomplete: '',
            names: {
                'cardNumber': 'cardnumber',
                'expiryDate': 'exp-date',
                'cvv': 'cvc',
                'phoneNumber': 'phone',
            },
            autocompletes: {
                'cardNumber': 'cc-number',
                'expiryDate': 'cc-exp',
                'cvv': 'cc-csc',
                'phoneNumber': 'tel',
            },
        },

        initialize: function () {
            this._super();
            this.initFieldData();
            this.value.extend({
                rateLimit: {method: "notifyWhenChangesStop", timeout: 1}
            });
            /** Re-apply additionalClasses */
            this._setClasses();

            return this;
        },

        initFieldData: function () {
            this.template = 'ui/form/field';
            this.elementTmpl = this.getTemplateForType(this.field.displayHints.formElement.type);
            this.tooltipTpl = 'Worldline_Connect/payment/product/field/tooltip-image';
            this.label = this.field.displayHints.label;

            /** Add validation rules. */
            if (this.field.dataRestrictions.isRequired) {
                this.setValidation(
                    'required-entry',
                    true
                );
            }
            this.setValidation(
                'worldline-field',
                {'productField': this.field}
            );

            this.placeholder = this.field.displayHints.placeholderLabel;

            if (this.account && this.account.attributeByKey[this.field.id]) {
                this.initAccountData();
            }

            if (this.names[this.field.id]) {
                this.inputName = this.names[this.field.id];
            }
            if (this.autocompletes[this.field.id]) {
                this.autocomplete = this.autocompletes[this.field.id];
            }

            if (this.field.displayHints.formElement.type === 'list') {
                this.initSelectData()
            }

            if (this.field.displayHints.tooltip) {
                this.initTooltip();
            }
        },

        initAccountData: function () {
            let accountAttribute = this.account.attributeByKey[this.field.id];
            this.value(accountAttribute.value);
            if (accountAttribute.status === "READ_ONLY") {
                this.disabled(true);
            }
        },

        initSelectData: function () {
            this.caption = this.field.displayHints.placeholderLabel;
            this.options = [];
            for (let item of this.field.displayHints.formElement.valueMapping) {
                this.options.push({
                    value: item.value,
                    label: item.displayName
                });
            }
        },

        initTooltip: function () {
            this.tooltip = {
                description: this.field.displayHints.tooltip.label,
                image: this.field.displayHints.tooltip.image
            }
        },

        getTemplateForType: function (type) {
            let templates = {
                text: 'Worldline_Connect/payment/product/field/input',
                checkbox: 'ui/form/element/single-checkbox',
                list: 'ui/form/element/select',
            };
            if (templates[type]) {
                return templates[type];
            }

            return false;
        },

        /**
         * Callback that fires when 'value' property is updated.
         * Apply mask to value; rate-limit parent method's validation
         */
        onUpdate: function (value) {

            /** Apply mask */
            if (this.value()) {
                let newValue;
                if (this.account && this.account.attributeByKey[this.field.id]) {
                    newValue = this.field.applyWildcardMask(this.value()).formattedValue;
                } else {
                    newValue = this.field.applyMask(this.value()).formattedValue;
                }
                this.value(newValue);
            }

            /** Rate-limit validation */
            if (!this.debouncedSuper) {
                this.debouncedSuper = _.debounce(
                    this._super.bind(this),
                    1000
                )
            }
            this.debouncedSuper(value);
        },

        /**
         * Overwritten because of apparently buggy parent method
         *
         * @param {(String|Object)} rule
         * @param {(Object|Boolean)} [options]
         * @returns {Abstract} Chainable.
         */
        setValidation: function (rule, options) {
            var rules = utils.copy(this.validation),
                changed;

            if (_.isObject(rule)) {
                _.extend(this.validation, rule);
            } else {
                this.validation[rule] = options;
            }
            changed = !utils.compare(rules, this.validation).equal;

            if (changed) {
                this.required(!!this.validation['required-entry']);
            }

            return this;
        },
    })
});
