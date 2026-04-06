/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'uiComponent',
    'ko'
], function (Component, ko) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/draft-manager',
            autoSaveDelay: 2000,
            savedTimeText: '',
            tracks: {
                savedTimeText: true
            }
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            this._super();

            this._isDirty = false;
            this._autoSaveTimer = null;
            this._lastSavedTime = null;
            this._statusUpdateInterval = null;

            return this;
        },

        /**
         * Mark the editor state as dirty and schedule an auto-save.
         */
        markDirty: function () {
            var self = this;

            this._isDirty = true;

            if (this._autoSaveTimer) {
                clearTimeout(this._autoSaveTimer);
            }

            this._autoSaveTimer = setTimeout(function () {
                var parent = self.source || self.parentComponent;

                if (parent && typeof parent.saveDraft === 'function') {
                    parent.saveDraft();
                }
            }, this.autoSaveDelay);
        },

        /**
         * Mark the editor state as clean and cancel any pending auto-save.
         */
        markClean: function () {
            this._isDirty = false;

            if (this._autoSaveTimer) {
                clearTimeout(this._autoSaveTimer);
                this._autoSaveTimer = null;
            }
        },

        /**
         * Check whether there are unsaved changes.
         *
         * @return {boolean}
         */
        isDirty: function () {
            return this._isDirty;
        },

        /**
         * Record the current time as the last saved time and start
         * updating the relative time text every 30 seconds.
         */
        updateSavedTime: function () {
            var self = this;

            this._lastSavedTime = new Date();
            this._computeSavedTimeText();

            if (this._statusUpdateInterval) {
                clearInterval(this._statusUpdateInterval);
            }

            this._statusUpdateInterval = setInterval(function () {
                self._computeSavedTimeText();
            }, 30000);
        },

        /**
         * Compute the relative time text from the last saved time.
         */
        _computeSavedTimeText: function () {
            var now,
                diffMs,
                diffSec,
                diffMin,
                diffHour;

            if (!this._lastSavedTime) {
                this.savedTimeText = '';

                return;
            }

            now = new Date();
            diffMs = now.getTime() - this._lastSavedTime.getTime();
            diffSec = Math.floor(diffMs / 1000);
            diffMin = Math.floor(diffSec / 60);
            diffHour = Math.floor(diffMin / 60);

            if (diffSec < 60) {
                this.savedTimeText = 'Draft saved ' + diffSec + 's ago';
            } else if (diffMin < 60) {
                this.savedTimeText = 'Draft saved ' + diffMin + 'm ago';
            } else {
                this.savedTimeText = 'Draft saved ' + diffHour + 'h ago';
            }
        },

        /**
         * Clear all timers and destroy the component.
         */
        destroy: function () {
            if (this._autoSaveTimer) {
                clearTimeout(this._autoSaveTimer);
                this._autoSaveTimer = null;
            }

            if (this._statusUpdateInterval) {
                clearInterval(this._statusUpdateInterval);
                this._statusUpdateInterval = null;
            }

            this._super();
        }
    });
});
