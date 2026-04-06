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
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/template-sidebar',
            urls: window.emailEditorConfig && window.emailEditorConfig.urls || {},
            formKey: window.emailEditorConfig && window.emailEditorConfig.formKey || ''
        },

        /**
         * Initialize component, set up observables and computed properties.
         *
         * @return {Object}
         */
        initialize: function () {
            this._super();

            this.observe(['searchQuery', 'groups', 'activeId', 'activeOverrideId', 'filterMode']);

            this.searchQuery('');
            this.groups([]);
            this.activeId('');
            this.activeOverrideId(null);
            this.filterMode('all');

            this.expandedGroups = ko.observable({});
            this.expandedTemplates = ko.observable({});

            this.filteredGroups = ko.computed(function () {
                var query = (this.searchQuery() || '').toLowerCase(),
                    allGroups = this.groups(),
                    filter = this.filterMode();

                return allGroups.reduce(function (result, group) {
                    var matchingTemplates = group.templates.filter(function (tpl) {
                        var matchesSearch = true,
                            matchesFilter = true,
                            hasOverrides = tpl.overrides && tpl.overrides.length > 0;

                        if (query) {
                            matchesSearch = (tpl.label && tpl.label.toLowerCase().indexOf(query) !== -1) ||
                                (tpl.id && tpl.id.toLowerCase().indexOf(query) !== -1);
                        }

                        if (filter === 'customized') {
                            matchesFilter = hasOverrides;
                        } else if (filter === 'defaults') {
                            matchesFilter = !hasOverrides;
                        }

                        return matchesSearch && matchesFilter;
                    });

                    if (matchingTemplates.length > 0) {
                        result.push({
                            key: group.key,
                            label: group.label,
                            templates: matchingTemplates
                        });
                    }

                    return result;
                }, []);
            }, this);

            this.templateCount = ko.computed(function () {
                var groups = this.groups(),
                    total = 0,
                    customized = 0;

                groups.forEach(function (group) {
                    group.templates.forEach(function (tpl) {
                        total++;

                        if (tpl.overrides && tpl.overrides.length > 0) {
                            customized++;
                        }
                    });
                });

                return {total: total, customized: customized, defaults: total - customized};
            }, this);

            return this;
        },

        /**
         * Set the sidebar filter mode.
         *
         * @param {string} mode
         */
        setFilter: function (mode) {
            this.filterMode(mode);
        },

        /**
         * Load template list from the server and populate groups.
         *
         * @return {jQuery.Deferred}
         */
        load: function () {
            var self = this;

            return this._ajax(this.urls.loadList, 'GET', {}).done(function (res) {
                if (res.success && res.templates) {
                    var grouped = [];

                    $.each(res.templates, function (groupKey, templates) {
                        grouped.push({
                            key: groupKey,
                            label: groupKey,
                            templates: templates
                        });
                    });

                    self.groups(grouped);
                }
            });
        },

        /**
         * Select a template by identifier, expand its parent group, and fire event.
         *
         * @param {string} identifier
         */
        select: function (identifier) {
            var groups = this.groups(),
                i, j;

            this.activeId(identifier);

            for (i = 0; i < groups.length; i++) {
                for (j = 0; j < groups[i].templates.length; j++) {
                    if (groups[i].templates[j].id === identifier) {
                        this._expandGroup(groups[i].key);
                        this.trigger('templateSelect', identifier);

                        return;
                    }
                }
            }

            this.trigger('templateSelect', identifier);
        },

        /**
         * Select a template from its data object.
         *
         * @param {Object} templateData
         */
        selectTemplate: function (templateData) {
            this.activeOverrideId(null);
            this.select(templateData.id);
        },

        /**
         * Select an override entry and fire overrideSelect event.
         *
         * @param {Object} overrideData
         * @param {Object} templateData
         */
        selectOverride: function (overrideData, templateData) {
            this.activeId(templateData.id);
            this.activeOverrideId(overrideData.entity_id);
            this.trigger('overrideSelect', {
                override: overrideData,
                template: templateData
            });
        },

        /**
         * Toggle the expanded state of a group.
         *
         * @param {Object} groupData
         */
        toggleGroup: function (groupData) {
            var map = this.expandedGroups(),
                key = groupData.key;

            map[key] = !map[key];
            this.expandedGroups(Object.assign({}, map));
        },

        /**
         * Check whether a group is currently expanded.
         *
         * @param {string} key
         * @return {boolean}
         */
        isGroupExpanded: function (key) {
            return ko.unwrap(this.expandedGroups())[key] === true;
        },

        /**
         * Toggle the expanded state of a template.
         *
         * @param {Object} templateData
         */
        toggleTemplate: function (templateData) {
            var map = this.expandedTemplates(),
                id = templateData.id;

            map[id] = !map[id];
            this.expandedTemplates(Object.assign({}, map));
        },

        /**
         * Check whether a template is currently expanded.
         *
         * @param {string} id
         * @return {boolean}
         */
        isTemplateExpanded: function (id) {
            return ko.unwrap(this.expandedTemplates())[id] === true;
        },

        /**
         * Reload the sidebar data from the server while preserving
         * the currently expanded groups, templates, and active selection.
         *
         * @return {jQuery.Deferred}
         */
        refresh: function () {
            var self = this,
                currentActiveId = this.activeId(),
                currentActiveOverrideId = this.activeOverrideId(),
                currentExpandedGroups = Object.assign({}, this.expandedGroups()),
                currentExpandedTemplates = Object.assign({}, this.expandedTemplates());

            return this.load().done(function () {
                self.expandedGroups(currentExpandedGroups);
                self.expandedTemplates(currentExpandedTemplates);

                if (currentActiveId) {
                    self.activeId(currentActiveId);
                }

                if (currentActiveOverrideId) {
                    self.activeOverrideId(currentActiveOverrideId);
                }
            });
        },

        /**
         * Mark a template as having or not having a draft.
         *
         * @param {string} identifier
         * @param {boolean} hasDraft
         */
        markDraft: function (identifier, hasDraft) {
            this.refresh();
        },

        /**
         * Rename an override entry by prompting the user for a new name.
         *
         * @param {Object} overrideData
         */
        renameOverride: function (overrideData) {
            var newName = prompt('Enter new name:', overrideData.label || ''),
                self = this;

            if (newName === null || newName === '') {
                return;
            }

            this._ajax(this.urls.renameDraft, 'POST', {
                entity_id: overrideData.entity_id,
                draft_name: newName
            }).done(function () {
                self.refresh();
            });
        },

        /**
         * Delete an override entry after user confirmation.
         *
         * @param {Object} overrideData
         */
        deleteOverride: function (overrideData) {
            var self = this;

            this.trigger('confirmAction', {
                title: $.mage.__('Delete Override'),
                message: $.mage.__('Are you sure you want to delete this override? This action cannot be undone.'),
                detail: '<strong>' + (overrideData.label || '') + '</strong> (' + overrideData.status + ')',
                actionLabel: $.mage.__('Delete'),
                type: 'danger',
                onConfirm: function () {
                    self._ajax(self.urls.deleteDraft, 'POST', {
                        entity_id: overrideData.entity_id
                    }).done(function () {
                        if (self.activeOverrideId() === overrideData.entity_id) {
                            self.activeOverrideId(null);
                        }

                        self.refresh();
                    });
                }
            });
        },

        /**
         * Toggle the active state of a published override.
         *
         * @param {Object} overrideData
         */
        toggleActive: function (overrideData) {
            var self = this;

            this._ajax(this.urls.toggleActive, 'POST', {
                entity_id: overrideData.entity_id
            }).done(function (res) {
                if (res.success) {
                    self.refresh();
                }
            });
        },

        /**
         * Fire event to edit the schedule of a published override.
         *
         * @param {Object} overrideData
         * @param {Object} templateData
         */
        editSchedule: function (overrideData, templateData) {
            this.trigger('editSchedule', {
                override: overrideData,
                template: templateData
            });
        },

        /**
         * Fire event to create a new draft for a template without changing active selection.
         *
         * @param {Object} templateData
         */
        createDraftFor: function (templateData) {
            this.activeId(templateData.id);
            this.trigger('createDraft', templateData.id);
        },

        /**
         * Format a date string into a short readable format (e.g. "Jan 15, 2026").
         *
         * @param {string} dateStr
         * @return {string}
         */
        formatDate: function (dateStr) {
            if (!dateStr) {
                return '';
            }

            try {
                var d = new Date(dateStr.replace(/-/g, '/')),
                    months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                              'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

                if (isNaN(d.getTime())) {
                    return dateStr;
                }

                return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
            } catch (e) {
                return dateStr;
            }
        },

        /**
         * Expand a template's children without toggling.
         *
         * @param {string} id
         */
        expandTemplate: function (id) {
            var map = this.expandedTemplates();

            if (!map[id]) {
                map[id] = true;
                this.expandedTemplates(Object.assign({}, map));
            }
        },

        /**
         * Expand a group by key without toggling.
         *
         * @param {string} key
         */
        _expandGroup: function (key) {
            var map = this.expandedGroups();

            if (!map[key]) {
                map[key] = true;
                this.expandedGroups(Object.assign({}, map));
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
