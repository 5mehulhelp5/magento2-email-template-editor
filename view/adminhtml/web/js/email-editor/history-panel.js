/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'jquery',
    'emailEditorDiffEngine'
], function ($, DiffEngine) {
    'use strict';

    /**
     * History panel component for template change history (backward compatibility wrapper)
     *
     * @param {Object} options
     * @constructor
     */
    function HistoryPanel(options) {
        this.options = options;
        this.modal = $(options.modal);
        this.listEl = $(options.listContainer);
        this.loadingEl = $(options.loadingEl);
        this.emptyEl = $(options.emptyEl);
        this.diffContainerEl = $('#ete-history-diff-container');
        this.diffViewEl = $('#ete-history-diff-view');
        this.diffLabelEl = $('#ete-diff-label');
        this._activePreviewId = null;
        this._entryCount = 0;
        this._bindClose();
        this._bindDiffBack();
    }

    /**
     * Bind close button
     */
    HistoryPanel.prototype._bindClose = function () {
        var self = this;

        this.modal.find('.ete-modal-close').on('click', function () {
            self.hide();
        });

        this.modal.on('click', function (e) {
            if ($(e.target).is(self.modal)) {
                self.hide();
            }
        });
    };

    /**
     * Bind the diff view back button
     */
    HistoryPanel.prototype._bindDiffBack = function () {
        var self = this;

        $('#ete-diff-back').on('click', function () {
            self._hideDiffView();
        });
    };

    /**
     * Show the history modal and load entries
     *
     * @param {string} identifier
     * @param {number} storeId
     */
    HistoryPanel.prototype.show = function (identifier, storeId) {
        this._activePreviewId = null;
        this._hideDiffView();
        this.modal.show();
        this._loadHistory(identifier, storeId);
    };

    /**
     * Hide the history modal and restore preview if needed
     */
    HistoryPanel.prototype.hide = function () {
        this.modal.hide();

        if (this._activePreviewId !== null) {
            this._activePreviewId = null;

            if (typeof this.options.onPreviewEnd === 'function') {
                this.options.onPreviewEnd();
            }
        }
    };

    /**
     * Load history entries via AJAX
     *
     * @param {string} identifier
     * @param {number} storeId
     */
    HistoryPanel.prototype._loadHistory = function (identifier, storeId) {
        var self = this,
            data = {
                template_identifier: identifier,
                store_id: storeId,
                form_key: this.options.formKey
            };

        this.loadingEl.show();
        this.emptyEl.hide();
        this.listEl.empty();

        $.ajax({
            url: this.options.urls.historyLoadList,
            type: 'GET',
            data: data,
            dataType: 'json'
        }).done(function (res) {
            self.loadingEl.hide();

            if (res.success && res.history && res.history.length > 0) {
                self._entryCount = res.history.length;
                self._renderEntries(res.history);
            } else {
                self._entryCount = 0;
                self.emptyEl.show();
            }
        }).fail(function () {
            self.loadingEl.hide();
            self._entryCount = 0;
            self.emptyEl.text('Failed to load history.').show();
        });
    };

    /**
     * Render history entries
     *
     * @param {Array} entries
     */
    HistoryPanel.prototype._renderEntries = function (entries) {
        var self = this,
            totalEntries = entries.length;

        $.each(entries, function (i, entry) {
            var entryEl = $('<div>').addClass('ete-history-entry').attr('data-history-id', entry.history_id),
                infoEl = $('<div>').addClass('ete-history-entry-info'),
                topEl = $('<div>').addClass('ete-history-entry-top'),
                actionsEl = $('<div>').addClass('ete-history-entry-actions'),
                actionBadge = $('<span>')
                    .addClass('ete-history-action-badge ete-action-' + entry.action)
                    .text(self._formatAction(entry.action)),
                dateEl = $('<span>').addClass('ete-history-date').text(entry.created_at),
                userEl = $('<span>').addClass('ete-history-user').text(entry.admin_username),
                hasContent = entry.action !== 'reset' && entry.action !== 'draft_discarded',
                isLastEntry = i === totalEntries - 1,
                diffBtn,
                previewBtn,
                revertBtn;

            topEl.append(actionBadge).append(userEl);
            infoEl.append(topEl).append(dateEl);
            entryEl.append(infoEl);

            if (hasContent) {
                if (!isLastEntry) {
                    diffBtn = $('<button>')
                        .addClass('ete-history-diff-btn')
                        .text('Diff')
                        .data('history-id', entry.history_id);

                    diffBtn.on('click', function () {
                        self._showDiff(entry.history_id);
                    });

                    actionsEl.append(diffBtn);
                }

                previewBtn = $('<button>')
                    .addClass('ete-history-preview-btn')
                    .text('Preview')
                    .data('history-id', entry.history_id);

                previewBtn.on('click', function () {
                    self._preview(entry.history_id, entryEl);
                });

                revertBtn = $('<button>')
                    .addClass('ete-history-revert-btn')
                    .text('Revert')
                    .data('history-id', entry.history_id);

                revertBtn.on('click', function () {
                    self._revert(entry.history_id);
                });

                actionsEl.append(previewBtn).append(revertBtn);
            }

            entryEl.append(actionsEl);
            self.listEl.append(entryEl);
        });
    };

    /**
     * Format action name for display
     *
     * @param {string} action
     * @return {string}
     */
    HistoryPanel.prototype._formatAction = function (action) {
        var labels = {
            'created': 'Created',
            'updated': 'Updated',
            'published': 'Published',
            'draft_saved': 'Draft Saved',
            'draft_discarded': 'Draft Discarded',
            'reset': 'Reset',
            'reverted': 'Reverted',
            'schedule_updated': 'Schedule Updated'
        };

        return labels[action] || action;
    };

    /**
     * Preview a history entry
     *
     * @param {number} historyId
     * @param {jQuery} entryEl
     */
    HistoryPanel.prototype._preview = function (historyId, entryEl) {
        var self = this;

        this.listEl.find('.ete-history-entry').removeClass('ete-history-entry-active');
        entryEl.addClass('ete-history-entry-active');
        this._activePreviewId = historyId;

        if (typeof this.options.onPreviewStart === 'function') {
            this.options.onPreviewStart();
        }

        $.ajax({
            url: this.options.urls.historyPreview,
            type: 'POST',
            data: {
                history_id: historyId,
                form_key: this.options.formKey
            },
            dataType: 'json'
        }).done(function (res) {
            if (self._activePreviewId !== historyId) {
                return;
            }

            if (res.success && typeof self.options.onPreview === 'function') {
                self.options.onPreview(res);
            } else if (!res.success) {
                alert(res.message || 'Failed to load preview.');
            }
        }).fail(function () {
            alert('Failed to load history preview.');
        });
    };

    /**
     * Revert to a history entry
     *
     * @param {number} historyId
     */
    HistoryPanel.prototype._revert = function (historyId) {
        var self = this;

        if (!confirm('Revert to this version? This will save it as a draft.')) {
            return;
        }

        $.ajax({
            url: this.options.urls.historyRevert,
            type: 'POST',
            data: {
                history_id: historyId,
                target_status: 'draft',
                form_key: this.options.formKey
            },
            dataType: 'json'
        }).done(function (res) {
            if (res.success) {
                self._activePreviewId = null;
                self.hide();

                if (typeof self.options.onRevert === 'function') {
                    self.options.onRevert(res);
                }
            } else {
                alert(res.message || 'Revert failed.');
            }
        }).fail(function () {
            alert('Failed to revert template.');
        });
    };

    /**
     * Show diff view for a history entry compared to its predecessor
     *
     * @param {number} historyId
     */
    HistoryPanel.prototype._showDiff = function (historyId) {
        var self = this;

        this.listEl.hide();
        this.emptyEl.hide();
        this.diffContainerEl.show();
        this.diffViewEl.html(
            '<div class="ete-loading" style="position:relative;min-height:80px;">' +
            '<div class="ete-spinner"></div><span>Loading diff...</span></div>'
        );
        this.diffLabelEl.text('');

        $.ajax({
            url: this.options.urls.historyDiff,
            type: 'POST',
            data: {
                history_id: historyId,
                form_key: this.options.formKey
            },
            dataType: 'json'
        }).done(function (res) {
            if (res.success) {
                self._renderDiffView(res);
            } else {
                self.diffViewEl.html(
                    '<div class="ete-diff-empty">' + (res.message || 'Failed to load diff.') + '</div>'
                );
            }
        }).fail(function () {
            self.diffViewEl.html('<div class="ete-diff-empty">Failed to load diff.</div>');
        });
    };

    /**
     * Render the diff view using the diff engine
     *
     * @param {Object} data
     */
    HistoryPanel.prototype._renderDiffView = function (data) {
        var hunks = DiffEngine.computeDiff(data.old_content, data.new_content),
            html = '',
            oldLabel = data.old_label + (data.old_date ? ' (' + data.old_date + ')' : ''),
            newLabel = data.new_label + (data.new_date ? ' (' + data.new_date + ')' : ''),
            i, j, line, lineClass, prefix, lineNum;

        this.diffLabelEl.text(oldLabel + '  \u2192  ' + newLabel);

        if (hunks.length === 0) {
            this.diffViewEl.html('<div class="ete-diff-empty">No changes detected between these versions.</div>');

            return;
        }

        for (i = 0; i < hunks.length; i++) {
            if (i > 0) {
                html += '<div class="ete-diff-separator">\u2022 \u2022 \u2022</div>';
            }

            html += '<div class="ete-diff-hunk">';

            for (j = 0; j < hunks[i].lines.length; j++) {
                line = hunks[i].lines[j];

                if (line.type === 'add') {
                    lineClass = 'ete-diff-line-add';
                    prefix = '+';
                    lineNum = '<span class="ete-diff-line-number ete-diff-ln-old"></span>' +
                              '<span class="ete-diff-line-number ete-diff-ln-new">' + line.newLine + '</span>';
                } else if (line.type === 'remove') {
                    lineClass = 'ete-diff-line-remove';
                    prefix = '-';
                    lineNum = '<span class="ete-diff-line-number ete-diff-ln-old">' + line.oldLine + '</span>' +
                              '<span class="ete-diff-line-number ete-diff-ln-new"></span>';
                } else {
                    lineClass = 'ete-diff-line-equal';
                    prefix = ' ';
                    lineNum = '<span class="ete-diff-line-number ete-diff-ln-old">' + line.oldLine + '</span>' +
                              '<span class="ete-diff-line-number ete-diff-ln-new">' + line.newLine + '</span>';
                }

                html += '<div class="ete-diff-line ' + lineClass + '">' +
                        lineNum +
                        '<span class="ete-diff-line-prefix">' + prefix + '</span>' +
                        '<span class="ete-diff-line-text">' + this._escapeHtml(line.text) + '</span>' +
                        '</div>';
            }

            html += '</div>';
        }

        this.diffViewEl.html(html);
    };

    /**
     * Hide the diff view and return to the history list
     */
    HistoryPanel.prototype._hideDiffView = function () {
        this.diffContainerEl.hide();
        this.diffViewEl.empty();
        this.diffLabelEl.text('');
        this.listEl.show();
    };

    /**
     * Escape HTML entities in a string
     *
     * @param {string} str
     * @return {string}
     */
    HistoryPanel.prototype._escapeHtml = function (str) {
        var div = document.createElement('div');

        div.appendChild(document.createTextNode(str));

        return div.innerHTML;
    };

    return HistoryPanel;
});
