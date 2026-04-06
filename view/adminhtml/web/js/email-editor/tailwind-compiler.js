/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define(['jquery'], function ($) {
    'use strict';

    /**
     * TailwindCSS CDN Compiler
     *
     * Creates a hidden iframe that loads TailwindCSS CDN script,
     * injects template HTML, and extracts the generated CSS.
     */
    return {
        /** @type {HTMLIFrameElement|null} */
        _iframe: null,

        /** @type {boolean} */
        _ready: false,

        /** @type {jQuery.Deferred|null} */
        _readyDeferred: null,

        /**
         * Initialize the hidden iframe with TailwindCSS CDN
         *
         * @returns {jQuery.Deferred}
         */
        init: function () {
            if (this._readyDeferred) {
                return this._readyDeferred;
            }

            var self = this,
                deferred = $.Deferred();

            this._readyDeferred = deferred;

            this._iframe = document.createElement('iframe');
            this._iframe.style.cssText = 'position:absolute;width:0;height:0;border:none;visibility:hidden;';
            this._iframe.sandbox = 'allow-scripts allow-same-origin';
            document.body.appendChild(this._iframe);

            var iframeDoc = this._iframe.contentDocument || this._iframe.contentWindow.document;

            iframeDoc.open();
            iframeDoc.write([
                '<!DOCTYPE html>',
                '<html><head>',
                '<script src="https://cdn.tailwindcss.com"><\/script>',
                '<script>',
                'tailwind.config = {',
                '  corePlugins: { preflight: false },',
                '  important: false',
                '};',
                'window._twReady = false;',
                'document.addEventListener("DOMContentLoaded", function() {',
                '  setTimeout(function() { window._twReady = true; }, 500);',
                '});',
                '<\/script>',
                '</head><body>',
                '<div id="tw-content"></div>',
                '</body></html>'
            ].join(''));
            iframeDoc.close();

            this._iframe.onload = function () {
                var checkReady = setInterval(function () {
                    try {
                        if (self._iframe.contentWindow._twReady) {
                            clearInterval(checkReady);
                            self._ready = true;
                            deferred.resolve();
                        }
                    } catch (e) {
                        clearInterval(checkReady);
                        deferred.reject(e);
                    }
                }, 200);

                setTimeout(function () {
                    clearInterval(checkReady);

                    if (!self._ready) {
                        self._ready = true;
                        deferred.resolve();
                    }
                }, 5000);
            };

            return deferred.promise();
        },

        /**
         * Compile HTML content to extract Tailwind CSS
         *
         * @param {string} htmlContent
         * @returns {jQuery.Deferred} Resolves with the generated CSS string
         */
        compile: function (htmlContent) {
            var self = this,
                deferred = $.Deferred();

            if (!this._iframe) {
                this.init().done(function () {
                    self._doCompile(htmlContent, deferred);
                }).fail(function () {
                    deferred.resolve('');
                });
            } else if (!this._ready) {
                this._readyDeferred.done(function () {
                    self._doCompile(htmlContent, deferred);
                }).fail(function () {
                    deferred.resolve('');
                });
            } else {
                this._doCompile(htmlContent, deferred);
            }

            return deferred.promise();
        },

        /**
         * Perform the actual compilation inside the iframe
         *
         * @param {string} htmlContent
         * @param {jQuery.Deferred} deferred
         * @private
         */
        _doCompile: function (htmlContent, deferred) {
            var self = this;

            try {
                var iframeDoc = this._iframe.contentDocument || this._iframe.contentWindow.document,
                    contentEl = iframeDoc.getElementById('tw-content');

                if (!contentEl) {
                    deferred.resolve('');

                    return;
                }

                contentEl.innerHTML = htmlContent;

                setTimeout(function () {
                    try {
                        var css = self._extractCss(iframeDoc);

                        deferred.resolve(css);
                    } catch (e) {
                        deferred.resolve('');
                    }
                }, 1500);
            } catch (e) {
                deferred.resolve('');
            }
        },

        /**
         * Extract the generated Tailwind CSS from the iframe document
         *
         * @param {Document} iframeDoc
         * @returns {string}
         * @private
         */
        _extractCss: function (iframeDoc) {
            var styles = iframeDoc.querySelectorAll('style'),
                css = '',
                styleText,
                i;

            for (i = 0; i < styles.length; i++) {
                styleText = styles[i].textContent || '';

                if (styleText.indexOf('tailwind') !== -1 ||
                    styleText.indexOf('--tw-') !== -1 ||
                    styleText.indexOf('.bg-') !== -1 ||
                    styleText.indexOf('.text-') !== -1 ||
                    styleText.indexOf('.p-') !== -1 ||
                    styleText.indexOf('.m-') !== -1 ||
                    styleText.indexOf('.flex') !== -1 ||
                    styleText.indexOf('.grid') !== -1) {
                    css += styleText + '\n';
                }
            }

            return css.trim();
        },

        /**
         * Destroy the iframe and clean up resources
         */
        destroy: function () {
            if (this._iframe && this._iframe.parentNode) {
                this._iframe.parentNode.removeChild(this._iframe);
            }

            this._iframe = null;
            this._ready = false;
            this._readyDeferred = null;
        }
    };
});
