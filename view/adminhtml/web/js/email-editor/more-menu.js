/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/more-menu',
            isOpen: false,
            hasDraft: false
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            this._super();
            this.observe(['isOpen', 'hasDraft']);

            $(document).on('click', this._onDocumentClick.bind(this));

            return this;
        },

        /**
         * Toggle the menu visibility
         */
        toggle: function () {
            this.isOpen(!this.isOpen());
        },

        /**
         * Close the menu
         */
        close: function () {
            this.isOpen(false);
        },

        /**
         * Open preview in new tab
         */
        previewInNewTab: function () {
            this.close();
            this.trigger('menuAction', 'previewInNewTab');
        },

        /**
         * Open version history
         */
        openVersionHistory: function () {
            this.close();
            this.trigger('menuAction', 'openVersionHistory');
        },

        /**
         * Delete draft action
         */
        deleteDraft: function () {
            this.close();
            this.trigger('menuAction', 'deleteDraft');
        },

        /**
         * Reset template to default
         */
        resetTemplate: function () {
            this.close();
            this.trigger('menuAction', 'resetTemplate');
        },

        /**
         * Close menu when clicking outside
         *
         * @param {Event} e
         * @private
         */
        _onDocumentClick: function (e) {
            if (this.isOpen() && !$(e.target).closest('.ete-more-menu, .ete-toolbar-action-more').length) {
                this.close();
            }
        },

        /**
         * @inheritDoc
         */
        destroy: function () {
            $(document).off('click', this._onDocumentClick);
            this._super();
        }
    });
});
