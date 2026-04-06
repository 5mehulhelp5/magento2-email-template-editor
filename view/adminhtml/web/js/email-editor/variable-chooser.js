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

    /** @type {number} */
    var MAX_RECENT = 5;

    /** @type {string} */
    var STORAGE_KEY = 'ete_recent_variables';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/variable-chooser',
            urls: window.emailEditorConfig && window.emailEditorConfig.urls || {},
            formKey: window.emailEditorConfig && window.emailEditorConfig.formKey || '',
            isOpen: false,
            searchQuery: '',
            groups: [],
            isLoading: false,
            recentCollapsed: false
        },

        /**
         * @inheritDoc
         */
        initialize: function () {
            this._super();

            this.observe([
                'isOpen',
                'searchQuery',
                'groups',
                'isLoading',
                'recentVariables',
                'recentCollapsed'
            ]);

            this._loaded = false;
            this._templateId = '';
            this._storeId = 0;
            this._expandedGroupsMap = ko.observable({});

            this.recentVariables(this._loadRecent());

            this.filteredGroups = ko.computed(function () {
                var query = (this.searchQuery() || '').toLowerCase(),
                    allGroups = this.groups();

                if (!query) {
                    return allGroups;
                }

                return allGroups.reduce(function (result, group) {
                    var matchingVariables = (group.variables || []).filter(function (variable) {
                        var value = (variable.value || '').toLowerCase(),
                            label = (variable.label || '').toLowerCase();

                        return value.indexOf(query) !== -1 || label.indexOf(query) !== -1;
                    });

                    if (matchingVariables.length) {
                        result.push({
                            label: group.label,
                            variables: matchingVariables
                        });
                    }

                    return result;
                }, []);
            }, this);

            this.totalCount = ko.computed(function () {
                var groups = this.groups(),
                    count = 0,
                    i;

                for (i = 0; i < groups.length; i++) {
                    count += (groups[i].variables || []).length;
                }

                return count;
            }, this);

            return this;
        },

        /**
         * Toggle the variable chooser panel open or closed.
         */
        toggle: function () {
            if (this.isOpen()) {
                this.close();
            } else {
                this.open();
            }
        },

        /**
         * Open the variable chooser panel. Loads groups on first open or when template changes.
         *
         * @param {string} [templateId]
         * @param {number} [storeId]
         */
        open: function (templateId, storeId) {
            var needsReload = !this._loaded;

            if (templateId !== undefined && templateId !== this._templateId) {
                this._templateId = templateId;
                needsReload = true;
            }

            if (storeId !== undefined && storeId !== this._storeId) {
                this._storeId = storeId;
                needsReload = true;
            }

            this.isOpen(true);
            this.searchQuery('');

            if (needsReload) {
                this.loadGroups(this._templateId, this._storeId);
            }
        },

        /**
         * Close the variable chooser panel.
         */
        close: function () {
            this.isOpen(false);
        },

        /**
         * Clear the search query.
         */
        clearSearch: function () {
            this.searchQuery('');
        },

        /**
         * Toggle group collapsed state.
         *
         * @param {Object} group
         */
        toggleGroup: function (group) {
            var key = group.label || '',
                map = this._expandedGroupsMap(),
                updated = {};

            Object.keys(map).forEach(function (k) {
                updated[k] = map[k];
            });

            updated[key] = !this.isGroupExpanded(group);
            this._expandedGroupsMap(updated);
        },

        /**
         * Check whether a group is expanded.
         *
         * @param {Object} group
         * @return {boolean}
         */
        isGroupExpanded: function (group) {
            var key = group.label || '',
                map = this._expandedGroupsMap();

            return map[key] !== false;
        },

        /**
         * Load variable groups from the server via AJAX.
         *
         * @param {string} [templateId]
         * @param {number} [storeId]
         */
        loadGroups: function (templateId, storeId) {
            var self = this,
                url = this.urls.variableLoadGroups || this.urls.sampleDataGetVariables,
                data = {form_key: this.formKey};

            if (templateId) {
                data.template_id = templateId;
            }

            if (storeId !== undefined) {
                data.store_id = storeId;
            }

            this.isLoading(true);

            $.ajax({
                url: url,
                type: 'GET',
                data: data,
                dataType: 'json'
            }).done(function (res) {
                var groups = [];

                if (res.groups) {
                    if (Array.isArray(res.groups)) {
                        groups = res.groups;
                    } else if (typeof res.groups === 'object') {
                        groups = self._transformVariables(res.groups);
                    }
                } else if (res.variables && typeof res.variables === 'object') {
                    groups = self._transformVariables(res.variables);
                }

                self.groups(groups);
                self._loaded = true;
            }).always(function () {
                self.isLoading(false);
            });
        },

        /**
         * Transform a flat variables object into the groups array format.
         *
         * @param {Object} variables
         * @return {Array}
         */
        _transformVariables: function (variables) {
            var groups = [];

            if (Array.isArray(variables)) {
                return variables;
            }

            Object.keys(variables).forEach(function (groupName) {
                var groupVars = variables[groupName],
                    items = [];

                if (Array.isArray(groupVars)) {
                    items = groupVars;
                } else if (typeof groupVars === 'object') {
                    Object.keys(groupVars).forEach(function (key) {
                        items.push({
                            value: key,
                            label: ''
                        });
                    });
                }

                groups.push({
                    label: groupName,
                    variables: items
                });
            });

            return groups;
        },

        /**
         * Handle a variable click: insert it and track in recent list.
         *
         * @param {Object} variable
         */
        onVariableClick: function (variable) {
            this._addRecent(variable);
            this.trigger('insertVariable', variable.value);
        },

        /**
         * Load recently used variables from localStorage.
         *
         * @return {Array}
         */
        _loadRecent: function () {
            try {
                var stored = localStorage.getItem(STORAGE_KEY);

                return stored ? JSON.parse(stored) : [];
            } catch (e) {
                return [];
            }
        },

        /**
         * Add a variable to the recently used list and persist.
         *
         * @param {Object} variable
         */
        _addRecent: function (variable) {
            var recent = this.recentVariables().slice(),
                existing = -1,
                i;

            for (i = 0; i < recent.length; i++) {
                if (recent[i].value === variable.value) {
                    existing = i;
                    break;
                }
            }

            if (existing !== -1) {
                recent.splice(existing, 1);
            }

            recent.unshift({
                value: variable.value,
                label: variable.label || ''
            });

            if (recent.length > MAX_RECENT) {
                recent = recent.slice(0, MAX_RECENT);
            }

            this.recentVariables(recent);

            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(recent));
            } catch (e) {
                // storage full or unavailable
            }
        }
    });
});
