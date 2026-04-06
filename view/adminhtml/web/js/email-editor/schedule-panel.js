/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'uiComponent',
    'ko',
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/calendar'
], function (Component, ko, $, uiAlert) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/schedule-panel',
            activeFrom: '',
            activeTo: '',
            showModal: false,
            modalFrom: '',
            modalTo: ''
        },

        /**
         * Initialize the schedule panel component.
         *
         * @return {Object}
         */
        initialize: function () {
            this._super();

            this.observe([
                'activeFrom',
                'activeTo',
                'showModal',
                'modalFrom',
                'modalTo'
            ]);

            this.hasSchedule = ko.computed(function () {
                return this.activeFrom() !== '' || this.activeTo() !== '';
            }, this);

            this.displayFrom = ko.computed(function () {
                return this.activeFrom() ? this._formatDate(this.activeFrom()) : 'No start date';
            }, this);

            this.displayTo = ko.computed(function () {
                return this.activeTo() ? this._formatDate(this.activeTo()) : 'No end date';
            }, this);

            this.statusLabel = ko.computed(function () {
                var from = this.activeFrom(),
                    to = this.activeTo(),
                    now, fromDate, toDate;

                if (from === '' && to === '') {
                    return '';
                }

                now = new Date();
                fromDate = from ? new Date(from.replace(' ', 'T')) : null;
                toDate = to ? new Date(to.replace(' ', 'T')) : null;

                if (fromDate && now < fromDate) {
                    return 'Scheduled';
                }

                if (toDate && now > toDate) {
                    return 'Expired';
                }

                return 'Active';
            }, this);

            this.statusBadgeCss = ko.computed(function () {
                var label = this.statusLabel();

                switch (label) {
                    case 'Scheduled':
                        return 'ete-schedule-badge-scheduled';
                    case 'Active':
                        return 'ete-schedule-badge-active';
                    case 'Expired':
                        return 'ete-schedule-badge-expired';
                    default:
                        return '';
                }
            }, this);

            this.statusCssClass = this.statusBadgeCss;

            this.graphFillCss = ko.computed(function () {
                var label = this.statusLabel();

                switch (label) {
                    case 'Scheduled':
                        return 'ete-fill-scheduled';
                    case 'Active':
                        return 'ete-fill-active';
                    case 'Expired':
                        return 'ete-fill-expired';
                    default:
                        return 'ete-fill-active';
                }
            }, this);

            this.graphTrackCss = ko.computed(function () {
                return this.statusLabel() === 'Scheduled'
                    ? 'ete-schedule-graph-track-countdown'
                    : '';
            }, this);

            this.progressWidth = ko.computed(function () {
                var from = this.activeFrom(),
                    to = this.activeTo(),
                    now, fromDate, toDate, total, elapsed, percent;

                if (!this.hasSchedule()) {
                    return '100%';
                }

                if (!from) {
                    return '100%';
                }

                now = new Date();
                fromDate = new Date(from.replace(' ', 'T'));

                if (!to) {
                    return now > fromDate ? '50%' : '0%';
                }

                toDate = new Date(to.replace(' ', 'T'));
                total = toDate.getTime() - fromDate.getTime();

                if (total <= 0) {
                    return '100%';
                }

                elapsed = now.getTime() - fromDate.getTime();
                percent = Math.round((elapsed / total) * 100);
                percent = Math.min(100, Math.max(0, percent));

                return percent + '%';
            }, this);

            this.progressPercent = ko.computed(function () {
                var w = this.progressWidth();

                return parseInt(w, 10) || 0;
            }, this);

            this.remainingText = ko.computed(function () {
                var from = this.activeFrom(),
                    to = this.activeTo(),
                    label = this.statusLabel(),
                    now, target, diff;

                if (!this.hasSchedule()) {
                    return '';
                }

                now = new Date();

                if (label === 'Scheduled' && from) {
                    target = new Date(from.replace(' ', 'T'));
                    diff = target.getTime() - now.getTime();

                    return diff > 0 ? 'Starts in ' + this._humanizeDuration(diff) : '';
                }

                if (label === 'Active' && to) {
                    target = new Date(to.replace(' ', 'T'));
                    diff = target.getTime() - now.getTime();

                    return diff > 0 ? 'Ends in ' + this._humanizeDuration(diff) : '';
                }

                if (label === 'Expired') {
                    return 'Schedule has ended';
                }

                return '';
            }, this);

            this.durationText = ko.computed(function () {
                var from = this.activeFrom(),
                    to = this.activeTo(),
                    fromDate, toDate, diff;

                if (!this.hasSchedule()) {
                    return 'Always active';
                }

                if (from && to) {
                    fromDate = new Date(from.replace(' ', 'T'));
                    toDate = new Date(to.replace(' ', 'T'));
                    diff = toDate.getTime() - fromDate.getTime();

                    return diff > 0 ? 'Duration: ' + this._humanizeDuration(diff) : '';
                }

                if (from && !to) {
                    return 'No end date';
                }

                if (!from && to) {
                    return 'No start date';
                }

                return '';
            }, this);

            return this;
        },

        /**
         * Set active date range.
         *
         * @param {string} from
         * @param {string} to
         */
        setDates: function (from, to) {
            this.activeFrom(from || '');
            this.activeTo(to || '');
        },

        /**
         * Get active date range for save requests.
         *
         * @return {Object}
         */
        getDates: function () {
            return {
                active_from: this.activeFrom(),
                active_to: this.activeTo()
            };
        },

        /**
         * Get the current schedule status as a lowercase key.
         *
         * @return {string}
         */
        getStatus: function () {
            var from = this.activeFrom(),
                to = this.activeTo(),
                now, fromDate, toDate;

            if (from === '' && to === '') {
                return 'none';
            }

            now = new Date();
            fromDate = from ? new Date(from.replace(' ', 'T')) : null;
            toDate = to ? new Date(to.replace(' ', 'T')) : null;

            if (fromDate && now < fromDate) {
                return 'scheduled';
            }

            if (toDate && now > toDate) {
                return 'expired';
            }

            return 'active';
        },

        /**
         * Clear both date fields.
         */
        clearDates: function () {
            this.activeFrom('');
            this.activeTo('');
        },

        /**
         * Open the schedule modal with current values pre-filled.
         */
        openModal: function () {
            this.modalFrom(this.activeFrom());
            this.modalTo(this.activeTo());
            this.showModal(true);
            this._initModalCalendars();
        },

        /**
         * Close the schedule modal.
         */
        closeModal: function () {
            this.showModal(false);
        },

        /**
         * Apply the modal date values and close.
         * Reads directly from DOM inputs to handle calendar widget sync.
         */
        applySchedule: function () {
            var $modal = $('.ete-modal:visible'),
                fromVal = $modal.find('.ete-schedule-modal-from').val(),
                toVal = $modal.find('.ete-schedule-modal-to').val(),
                fromTime, toTime;

            if (fromVal !== undefined) {
                this.modalFrom(fromVal);
            }

            if (toVal !== undefined) {
                this.modalTo(toVal);
            }

            if (this.modalFrom() && this.modalTo()) {
                fromTime = new Date(this.modalFrom().replace(' ', 'T')).getTime();
                toTime = new Date(this.modalTo().replace(' ', 'T')).getTime();

                if (fromTime >= toTime) {
                    uiAlert({
                        title: $.mage.__('Invalid Date Range'),
                        content: $.mage.__('Active From must be before Active To.')
                    });

                    return;
                }
            }

            this.closeModal();
            this.trigger('scheduleChange', {
                active_from: this.modalFrom(),
                active_to: this.modalTo()
            });
        },

        /**
         * Initialize calendar widgets on the schedule modal inputs.
         */
        _initModalCalendars: function () {
            var self = this;

            setTimeout(function () {
                var $fromInput = $('.ete-schedule-modal-from:visible'),
                    $toInput = $('.ete-schedule-modal-to:visible'),
                    calendarOpts = {
                        dateFormat: 'yyyy-MM-dd',
                        timeFormat: 'HH:mm:ss',
                        showsTime: true,
                        changeMonth: true,
                        changeYear: true
                    };

                if ($fromInput.length && !$fromInput.data('calendarInitialized')) {
                    $fromInput.calendar(calendarOpts);
                    $fromInput.data('calendarInitialized', true);

                    $fromInput.on('change', function () {
                        self.modalFrom($(this).val());
                    });
                }

                if ($toInput.length && !$toInput.data('calendarInitialized')) {
                    $toInput.calendar(calendarOpts);
                    $toInput.data('calendarInitialized', true);

                    $toInput.on('change', function () {
                        self.modalTo($(this).val());
                    });
                }
            }, 150);
        },

        /**
         * Format a date string into a readable format.
         *
         * @param {string} dateStr
         * @return {string}
         */
        _formatDate: function (dateStr) {
            if (!dateStr) {
                return '';
            }

            try {
                var d = new Date(dateStr.replace(' ', 'T')),
                    months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                              'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    hours = d.getHours(),
                    mins = d.getMinutes(),
                    pad = function (n) { return n < 10 ? '0' + n : '' + n; };

                if (isNaN(d.getTime())) {
                    return dateStr;
                }

                return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear() +
                    ' ' + pad(hours) + ':' + pad(mins);
            } catch (e) {
                return dateStr;
            }
        },

        /**
         * Convert milliseconds into a human-readable duration.
         *
         * @param {number} ms
         * @return {string}
         */
        _humanizeDuration: function (ms) {
            var seconds = Math.floor(ms / 1000),
                minutes = Math.floor(seconds / 60),
                hours = Math.floor(minutes / 60),
                days = Math.floor(hours / 24);

            if (days > 0) {
                return days + (days === 1 ? ' day' : ' days');
            }

            if (hours > 0) {
                return hours + (hours === 1 ? ' hour' : ' hours');
            }

            if (minutes > 0) {
                return minutes + (minutes === 1 ? ' minute' : ' minutes');
            }

            return 'less than a minute';
        },

        /**
         * Remove the current schedule and notify listeners.
         */
        removeSchedule: function () {
            this.trigger('scheduleChange', {
                active_from: '',
                active_to: ''
            });
        }
    });
});
