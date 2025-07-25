/**
 * Copyright 2022 Adobe
 * All Rights Reserved.
 */

define([
    'Magento_Catalog/js/components/dynamic-rows-import-custom-options',
    'underscore',
    'mageUtils',
    'uiLayout',
    'rjsResolver'
], function (DrCustomOptions, _, utils, layout, resolver) {
    'use strict';

    return DrCustomOptions.extend({
        defaults: {
            sizesConfig: {
                component: 'Magento_Ui/js/grid/paging/sizes',
                name: '${ $.name }_sizes',
                options: {
                    '20': {
                        value: 20,
                        label: 20
                    },
                    '30': {
                        value: 30,
                        label: 30
                    },
                    '50': {
                        value: 50,
                        label: 50
                    },
                    '100': {
                        value: 100,
                        label: 100
                    },
                    '200': {
                        value: 200,
                        label: 200
                    }
                },
                storageConfig: {
                    provider: '${ $.storageConfig.provider }',
                    namespace: '${ $.storageConfig.namespace }'
                },
                enabled: false
            },
            links: {
                options: '${ $.sizesConfig.name }:options',
                pageSize: '${ $.sizesConfig.name }:value'
            },
            listens: {
                'pageSize': 'onPageSizeChange'
            },
            modules: {
                sizes: '${ $.sizesConfig.name }'
            }
        },

        /**
         * Initializes paging component.
         *
         * @returns {Paging} Chainable.
         */
        initialize: function () {
            this._super()
                .initSizes();

            return this;
        },

        /**
         * Initializes sizes component.
         *
         * @returns {Paging} Chainable.
         */
        initSizes: function () {
            if (this.sizesConfig.enabled) {
                layout([this.sizesConfig]);
            }

            return this;
        },

        /**
         * Initializes observable properties.
         *
         * @returns {Paging} Chainable.
         */
        initObservable: function () {
            this._super()
                .track([
                    'pageSize'
                ]);

            return this;
        },

        /**
         * Handles changes of the page size.
         */
        onPageSizeChange: function () {
            resolver(function () {
                if (this.elems().length) {
                    this.reload();
                }
            }, this);
        }
    });
});
