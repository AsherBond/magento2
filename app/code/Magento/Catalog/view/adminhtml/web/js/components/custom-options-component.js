/**
 * Copyright 2017 Adobe
 * All Rights Reserved.
 */

define([
    'underscore',
    'Magento_Ui/js/form/element/abstract'
], function (_, Abstract) {
    'use strict';

    return Abstract.extend({
        /**
         * {@inheritdoc}
         */
        setInitialValue: function () {
            this._super();

            this.addBefore(this.addbefore);

            return this;
        },

        /**
         * {@inheritdoc}
         */
        initObservable: function () {
            this._super();

            this.observe('addBefore');

            return this;
        }
    });
});
