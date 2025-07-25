/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */

define([
    'Magento_Ui/js/form/element/abstract'
], function (Abstract) {
    'use strict';

    return Abstract.extend({
        defaults: {
            valueUpdate: 'input',
            isInteger: true,
            validation: {
                'validate-number': true
            }
        },

        /**
         * @inheritdoc
         */
        setInitialValue: function () {
            this.initialValue = this.getInitialValue();

            if (this.initialValue === undefined || this.initialValue === '') {
                this.initialValue = 1;
            }

            if (this.value.peek() !== this.initialValue) {
                this.value(this.initialValue);
            }

            this.on('value', this.onUpdate.bind(this));
            this.isUseDefault(this.disabled());

            return this;
        },

        /**
         * @inheritdoc
         */
        onUpdate: function () {
            this.validation['validate-digits'] = this.isInteger;
            this._super();
        },

        /**
         * @inheritdoc
         */
        hasChanged: function () {
            var notEqual = this.value() !== this.initialValue.toString();

            return !this.visible() ? false : notEqual;
        }
    });
});
