/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/calendar'
], function (Component, ko, $) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/publish-dialog',
            isVisible: false,
            comment: '',
            scheduleMode: 'immediately',
            scheduleFrom: '',
            scheduleTo: '',
            feedbackMessage: '',
            hasError: false,
            changesSummary: [],
            targetStore: '',
            templateName: ''
        },

        /**
         * Initialize the publish dialog component.
         *
         * @return {Object}
         */
        initialize: function () {
            this._super();

            this.observe([
                'isVisible',
                'comment',
                'scheduleMode',
                'scheduleFrom',
                'scheduleTo',
                'feedbackMessage',
                'hasError',
                'changesSummary',
                'targetStore',
                'templateName'
            ]);

            this._onPublishCallback = null;
            this._calendarInitialized = false;

            this._initCalendarWatcher();

            return this;
        },

        /**
         * Open the publish dialog with the given parameters.
         *
         * @param {Object} params
         * @param {Function} params.onPublish
         * @param {string} [params.activeFrom]
         * @param {string} [params.activeTo]
         */
        open: function (params) {
            this.comment('');
            this.scheduleMode('immediately');
            this.scheduleFrom(params.activeFrom || '');
            this.scheduleTo(params.activeTo || '');
            this.feedbackMessage('');
            this.hasError(false);
            this.changesSummary(params.changesSummary || []);
            this.targetStore(params.targetStore || '');
            this.templateName(params.templateName || '');
            this._onPublishCallback = params.onPublish || null;
            this._calendarInitialized = false;
            this.isVisible(true);
        },

        /**
         * Close the publish dialog and reset all fields.
         */
        close: function () {
            this.isVisible(false);
            this.comment('');
            this.scheduleMode('immediately');
            this.scheduleFrom('');
            this.scheduleTo('');
            this.feedbackMessage('');
            this.hasError(false);
            this._onPublishCallback = null;
        },

        /**
         * Confirm the publish action. Validates scheduled dates when in schedule mode,
         * then invokes the publish callback with comment and date range, and closes the dialog.
         */
        confirm: function () {
            var fromVal, toVal;

            if (this.scheduleMode() === 'scheduled') {
                fromVal = $('.ete-schedule-from-input:visible').val() || this.scheduleFrom();
                toVal = $('.ete-schedule-to-input:visible').val() || this.scheduleTo();

                if (!fromVal && !toVal) {
                    this.showError('Please select at least one date.');

                    return;
                }

                if (fromVal && toVal && new Date(fromVal) >= new Date(toVal)) {
                    this.showError('Active From must be before Active To.');

                    return;
                }

                this.scheduleFrom(fromVal);
                this.scheduleTo(toVal);
            }

            if (typeof this._onPublishCallback === 'function') {
                this._onPublishCallback(
                    this.comment(),
                    this.scheduleMode() === 'scheduled' ? this.scheduleFrom() : '',
                    this.scheduleMode() === 'scheduled' ? this.scheduleTo() : ''
                );
            }

            this.close();
        },

        /**
         * Display an error message in the dialog feedback area.
         *
         * @param {string} message
         */
        showError: function (message) {
            this.feedbackMessage(message);
            this.hasError(true);
        },

        /**
         * Watch scheduleMode changes and initialize calendar when switching to scheduled.
         */
        _initCalendarWatcher: function () {
            var self = this;

            this.scheduleMode.subscribe(function (mode) {
                if (mode === 'scheduled') {
                    setTimeout(function () {
                        self._initCalendar();
                    }, 150);
                }
            });
        },

        /**
         * Initialize the Magento calendar widgets on the schedule date inputs.
         */
        _initCalendar: function () {
            var self = this,
                $fromInput = $('.ete-schedule-from-input:visible'),
                $toInput = $('.ete-schedule-to-input:visible'),
                calendarOpts = {
                    dateFormat: 'yyyy-MM-dd',
                    timeFormat: 'HH:mm:ss',
                    showsTime: true,
                    changeMonth: true,
                    changeYear: true
                };

            if (($fromInput.length || $toInput.length) && !this._calendarInitialized) {
                this._calendarInitialized = true;

                if ($fromInput.length) {
                    $fromInput.calendar(calendarOpts);

                    $fromInput.on('change', function () {
                        self.scheduleFrom($(this).val());
                    });
                }

                if ($toInput.length) {
                    $toInput.calendar(calendarOpts);

                    $toInput.on('change', function () {
                        self.scheduleTo($(this).val());
                    });
                }
            }
        }
    });
});
