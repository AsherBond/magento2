/**
 * Copyright 2015 Adobe
 * All Rights Reserved.
 */

define([], function () {
    'use strict';

    var cache = [];

    return {
        /**
         * @param {String} addressKey
         * @return {*}
         */
        get: function (addressKey) {
            if (cache[addressKey]) {
                return cache[addressKey];
            }

            return false;
        },

        /**
         * @param {String} addressKey
         * @param {*} data
         */
        set: function (addressKey, data) {
            cache[addressKey] = data;
        }
    };
});
