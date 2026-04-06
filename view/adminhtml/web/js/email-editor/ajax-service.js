/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define(['jquery'], function ($) {
    'use strict';

    /**
     * Shared AJAX helper with form_key and store_id injection
     *
     * @param {Object} config
     * @param {string} config.formKey
     * @param {Function} config.getStoreId
     */
    return function (config) {
        var formKey = config.formKey || '',
            getStoreId = config.getStoreId || function () { return 0; };

        return {
            /**
             * Perform an AJAX request with form_key and store_id
             *
             * @param {string} url
             * @param {Object} [data]
             * @param {string} [method]
             * @returns {jQuery.Deferred}
             */
            request: function (url, data, method) {
                data = data || {};
                data.form_key = formKey;
                data.store_id = getStoreId();

                return $.ajax({
                    url: url,
                    type: method || 'GET',
                    data: data,
                    dataType: 'json'
                });
            },

            /**
             * GET request shorthand
             *
             * @param {string} url
             * @param {Object} [data]
             * @returns {jQuery.Deferred}
             */
            get: function (url, data) {
                return this.request(url, data, 'GET');
            },

            /**
             * POST request shorthand
             *
             * @param {string} url
             * @param {Object} [data]
             * @returns {jQuery.Deferred}
             */
            post: function (url, data) {
                return this.request(url, data, 'POST');
            }
        };
    };
});
