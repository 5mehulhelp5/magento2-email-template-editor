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
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/draft-list-panel',
            urls: window.emailEditorConfig && window.emailEditorConfig.urls || {},
            formKey: window.emailEditorConfig && window.emailEditorConfig.formKey || ''
        },

        /**
         * Initialize component and set up observables.
         *
         * @return {Object}
         */
        initialize: function () {
            this._super();

            this.observe(['drafts', 'activeDraftId']);

            this.drafts([]);
            this.activeDraftId(null);

            return this;
        },

        /**
         * Load drafts for a given template identifier and store.
         *
         * @param {string} templateIdentifier
         * @param {number} storeId
         * @return {jQuery.Deferred}
         */
        loadDrafts: function (templateIdentifier, storeId) {
            var self = this;

            return this._ajax(this.urls.loadDrafts, 'GET', {
                template_identifier: templateIdentifier,
                store_id: storeId
            }).done(function (res) {
                if (res.drafts) {
                    self.setDrafts(res.drafts);
                }
            });
        },

        /**
         * Select a draft and fire the draftSelect event.
         *
         * @param {Object} draftData
         */
        selectDraft: function (draftData) {
            this.activeDraftId(draftData.entity_id);
            this.trigger('draftSelect', draftData);
        },

        /**
         * Fire the draftCreate event.
         */
        createDraft: function () {
            this.trigger('draftCreate');
        },

        /**
         * Prompt for a new name and rename the draft on the server.
         *
         * @param {Object} draftData
         */
        renameDraft: function (draftData) {
            var newName = prompt('Enter new draft name:', draftData.draft_name || ''),
                self = this;

            if (newName === null || newName === '') {
                return;
            }

            this._ajax(this.urls.renameDraft, 'POST', {
                entity_id: draftData.entity_id,
                draft_name: newName
            }).done(function () {
                self._reloadDraftsFromParent();
            });
        },

        /**
         * Duplicate a draft on the server.
         *
         * @param {Object} draftData
         */
        duplicateDraft: function (draftData) {
            var self = this;

            this._ajax(this.urls.duplicateDraft, 'POST', {
                entity_id: draftData.entity_id
            }).done(function () {
                self._reloadDraftsFromParent();
            });
        },

        /**
         * Delete a draft after confirmation.
         *
         * @param {Object} draftData
         */
        deleteDraft: function (draftData) {
            var self = this;

            this.trigger('confirmAction', {
                title: $.mage.__('Delete Draft'),
                message: $.mage.__('Are you sure you want to delete this draft? This action cannot be undone.'),
                detail: '<strong>' + (draftData.draft_name || $.mage.__('Untitled Draft')) + '</strong>',
                actionLabel: $.mage.__('Delete'),
                type: 'danger',
                onConfirm: function () {
                    self._ajax(self.urls.deleteDraft, 'POST', {
                        entity_id: draftData.entity_id
                    }).done(function () {
                        self._reloadDraftsFromParent();
                    });
                }
            });
        },

        /**
         * Set the drafts array and reconcile the active draft ID.
         *
         * @param {Array} draftsArray
         */
        setDrafts: function (draftsArray) {
            var currentId = this.activeDraftId(),
                found = false,
                i;

            this.drafts(draftsArray);

            if (currentId !== null) {
                for (i = 0; i < draftsArray.length; i++) {
                    if (draftsArray[i].entity_id === currentId) {
                        found = true;
                        break;
                    }
                }
            }

            if (!found) {
                this.activeDraftId(draftsArray.length > 0 ? draftsArray[0].entity_id : null);
            }
        },

        /**
         * Reload drafts via the parent component.
         */
        _reloadDraftsFromParent: function () {
            var parent = this.source || this.parentComponent;

            if (parent && typeof parent.loadDrafts === 'function') {
                parent.loadDrafts();
            }
        },

        /**
         * Perform an AJAX request with form_key injection.
         *
         * @param {string} url
         * @param {string} method
         * @param {Object} data
         * @return {jQuery.Deferred}
         */
        _ajax: function (url, method, data) {
            data.form_key = this.formKey;

            return $.ajax({
                url: url,
                type: method,
                data: data,
                dataType: 'json'
            });
        }
    });
});
