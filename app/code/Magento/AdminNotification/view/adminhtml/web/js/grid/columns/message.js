/**
 * Copyright 2016 Adobe
 * All Rights Reserved.
 */

/**
 * @api
 */
define([
    'Magento_Ui/js/grid/columns/column',
    'underscore'
], function (Column, _) {
    'use strict'; // eslint-disable-line strict

    return Column.extend({
        defaults: {
            bodyTmpl: 'Magento_AdminNotification/grid/cells/message',
            messageIndex: 'text',
            fieldClass: {
                message: true,
                'message-warning': false,
                'message-progress': false,
                'message-success': false,
                'message-error': false
            },
            statusMap: {
                0: 'info',
                1: 'progress',
                2: 'success',
                3: 'error'
            }
        },

        /** @inheritdoc */
        getLabel: function (record) {
            return record[this.messageIndex];
        },

        /**
         * Proxy to getLabel function with UnsanitizedHtml suffix
         *
         * @param {Object} record
         * @returns {String}
         */
        getLabelUnsanitizedHtml: function (record) {
            return this.getLabel(record);
        },

        /** @inheritdoc */
        getFieldClass: function ($row) {
            var status = this.statusMap[$row.status] || 'warning',
                result = {};

            result['message-' + status] = true;
            result = _.extend({}, this.fieldClass, result);

            return result;
        }
    });
});
