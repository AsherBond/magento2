/**
 * Copyright 2018 Adobe
 * All Rights Reserved.
 */

define([
    'jquery',
    'jquery-ui-modules/widget'
], function ($) {
    'use strict';

    /**
     * @api
     */
    $.widget('mage.captcha', {
        options: {
            refreshClass: 'refreshing',
            reloadSelector: '.captcha-reload',
            imageSelector: '.captcha-img',
            imageLoader: ''
        },

        /**
         * Method binds click event to reload image
         * @private
         */
        _create: function () {
            this.element.on('click', this.options.reloadSelector, $.proxy(this.refresh, this));
        },

        /**
         * Method triggers an AJAX request to refresh the CAPTCHA image
         */
        refresh: function () {
            var imageLoader = this.options.imageLoader;

            if (imageLoader) {
                this.element.find(this.options.imageSelector).attr('src', imageLoader);
            }
            this.element.addClass(this.options.refreshClass);

            $.ajax({
                url: this.options.url,
                type: 'post',
                dataType: 'json',
                context: this,
                data: {
                    'formId': this.options.type
                },

                /**
                 * @param {Object} response
                 */
                success: function (response) {
                    if (response.imgSrc) {
                        this.element.find(this.options.imageSelector).attr('src', response.imgSrc);
                    }
                },

                /** Complete callback. */
                complete: function () {
                    this.element.removeClass(this.options.refreshClass);
                }
            });
        }
    });

    return $.mage.captcha;
});
