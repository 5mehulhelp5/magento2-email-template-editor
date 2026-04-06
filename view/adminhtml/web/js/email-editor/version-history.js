/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'uiComponent',
    'ko',
    'jquery',
    'emailEditorDiffEngine'
], function (Component, ko, $, DiffEngine) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/version-history',
            urls: window.emailEditorConfig && window.emailEditorConfig.urls || {},
            formKey: window.emailEditorConfig && window.emailEditorConfig.formKey || ''
        },

        /**
         * Initialize the version history component.
         *
         * @return {Object}
         */
        initialize: function () {
            this._super();

            this.observe({
                isVisible: false,
                isLoading: false,
                entries: [],
                activePreviewId: null,
                showDiff: false,
                diffHtml: '',
                diffLabel: ''
            });

            this._currentIdentifier = '';
            this._currentStoreId = 0;

            return this;
        },

        /**
         * Show the version history panel and load entries.
         *
         * @param {string} identifier
         * @param {number} storeId
         */
        show: function (identifier, storeId) {
            this._currentIdentifier = identifier;
            this._currentStoreId = storeId;
            this.isVisible(true);
            this._loadVersions();
        },

        /**
         * Close the version history panel.
         */
        close: function () {
            this.isVisible(false);
            this.showDiff(false);
            this.trigger('historyClose');
        },

        /**
         * Load version entries from the server.
         */
        _loadVersions: function () {
            var self = this;

            this.isLoading(true);

            this._ajax(this.urls.versionLoadList, 'GET', {
                template_identifier: this._currentIdentifier,
                store_id: this._currentStoreId
            }).done(function (res) {
                var versions = res.versions || [];

                self.entries(versions);
            }).always(function () {
                self.isLoading(false);
            });
        },

        /**
         * Preview a specific version entry.
         *
         * @param {Object} entry
         */
        previewVersion: function (entry) {
            var self = this;

            this.activePreviewId(entry.version_id);
            this.trigger('historyPreviewStart');

            this._ajax(this.urls.versionPreview, 'POST', {
                version_id: entry.version_id
            }).done(function (res) {
                self.trigger('historyPreview', res);
            });
        },

        /**
         * Load and display a diff for a specific version entry against the previous version.
         *
         * @param {Object} entry
         */
        showVersionDiff: function (entry) {
            var self = this,
                entries = this.entries(),
                currentIndex = -1,
                previousEntry = null,
                i;

            for (i = 0; i < entries.length; i++) {
                if (entries[i].version_id === entry.version_id) {
                    currentIndex = i;
                    break;
                }
            }

            if (currentIndex >= 0 && currentIndex < entries.length - 1) {
                previousEntry = entries[currentIndex + 1];
            }

            if (!previousEntry) {
                this.diffHtml('<div class="ete-diff-empty">No previous version to compare against.</div>');
                this.diffLabel('v' + entry.version_number + ' (initial)');
                this.showDiff(true);

                return;
            }

            this._ajax(this.urls.versionDiff, 'POST', {
                version_id_a: previousEntry.version_id,
                version_id_b: entry.version_id
            }).done(function (res) {
                if (!res.success) {
                    return;
                }

                var oldContent = res.version_a ? (res.version_a.content || '') : '',
                    newContent = res.version_b ? (res.version_b.content || '') : '',
                    hunks = DiffEngine.computeDiff(oldContent, newContent),
                    html = '',
                    i, j, line, lineClass, oldLn, newLn, text, prefix;

                for (i = 0; i < hunks.length; i++) {
                    html += '<div class="ete-diff-hunk">';

                    for (j = 0; j < hunks[i].lines.length; j++) {
                        line = hunks[i].lines[j];

                        if (line.type === 'add') {
                            lineClass = 'ete-diff-line-add';
                            oldLn = '';
                            newLn = line.newLine;
                            prefix = '+';
                        } else if (line.type === 'remove') {
                            lineClass = 'ete-diff-line-remove';
                            oldLn = line.oldLine;
                            newLn = '';
                            prefix = '-';
                        } else {
                            lineClass = 'ete-diff-line-equal';
                            oldLn = line.oldLine;
                            newLn = line.newLine;
                            prefix = ' ';
                        }

                        text = line.text;

                        html += '<div class="ete-diff-line ' + lineClass + '">' +
                            '<span class="ete-diff-line-number ete-diff-ln-old">' + oldLn + '</span>' +
                            '<span class="ete-diff-line-number ete-diff-ln-new">' + newLn + '</span>' +
                            '<span class="ete-diff-line-prefix">' + prefix + '</span>' +
                            '<span class="ete-diff-line-text">' + self._escapeHtml(text) + '</span>' +
                            '</div>';
                    }

                    html += '</div>';
                }

                self.diffHtml(html);
                self.diffLabel('v' + entry.version_number + ' changes');
                self.showDiff(true);
            });
        },

        /**
         * Hide the diff view.
         */
        hideDiff: function () {
            this.showDiff(false);
        },

        /**
         * Restore a specific version after user confirmation.
         *
         * @param {Object} entry
         */
        restoreVersion: function (entry) {
            var self = this;

            this.trigger('confirmAction', {
                title: $.mage.__('Restore Version'),
                message: $.mage.__('Restore version v') + entry.version_number + $.mage.__('? This will create a new draft with this content.'),
                detail: entry.version_comment
                    ? '<strong>' + $.mage.__('Comment:') + '</strong> ' + entry.version_comment
                    : '',
                actionLabel: $.mage.__('Restore'),
                type: 'primary',
                onConfirm: function () {
                    self._ajax(self.urls.versionRestore, 'POST', {
                        version_id: entry.version_id,
                        template_identifier: self._currentIdentifier,
                        store_id: self._currentStoreId
                    }).done(function (res) {
                        self.trigger('historyRestore', res);
                        self.close();
                    });
                }
            });
        },

        /**
         * Perform an AJAX request with automatic form_key injection.
         *
         * @param {string} url
         * @param {string} method
         * @param {Object} data
         * @return {Object}
         */
        _ajax: function (url, method, data) {
            data.form_key = this.formKey;

            return $.ajax({
                url: url,
                type: method,
                data: data,
                dataType: 'json'
            });
        },

        /**
         * Escape HTML entities in a string.
         *
         * @param {string} str
         * @return {string}
         */
        _escapeHtml: function (str) {
            var div = document.createElement('div');

            div.appendChild(document.createTextNode(str));

            return div.innerHTML;
        }
    });
});
