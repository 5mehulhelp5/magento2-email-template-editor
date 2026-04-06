/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

define([
    'underscore',
    'uiComponent',
    'ko',
    'jquery',
    'uiRegistry',
    'Hryvinskyi_EmailTemplateEditor/js/email-editor/tailwind-compiler',
    'Magento_Ui/js/modal/alert'
], function (_, Component, ko, $, registry, tailwindCompiler, uiAlert) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Hryvinskyi_EmailTemplateEditor/email-editor/email-editor',
            urls: {},
            formKey: '',
            storeId: 0,
            stores: [],
            selectedTemplate: '',
            isEnabled: true
        },

        /** @type {number|null} */
        _previewDebounceTimer: null,

        /** @type {number|null} */
        _entitySearchTimer: null,

        /** @type {string} */
        _lastTailwindCss: '',

        /** @type {Object} */
        _providerEntitySelectionMap: {},

        /** @type {string|null} */
        _currentEntityId: null,

        /** @type {boolean} */
        _suppressChangeEvents: false,

        /** @type {Array<jQuery.jqXHR>} */
        _pendingRequests: [],

        /**
         * Initialize the email editor orchestrator, create observables,
         * child components, subscriptions, and load initial data.
         *
         * @return {Object}
         */
        initialize: function () {
            this._super();

            this.observe([
                'isInitialLoading',
                'currentTemplateId',
                'currentTemplateStatus',
                'hasDraft',
                'hasPublished',
                'isOverrideActive',
                'subject',
                'statusText',
                'statusCssClass',
                'statusBarText',
                'showDraftBadge',
                'showScheduleBadge',
                'showExpiredBadge',
                'tailwindCssOutput',
                'showCustomData',
                'customDataJson',
                'selectedProvider',
                'providers',
                'dataSourceLabel',
                'showEntitySearch',
                'entitySearchQuery',
                'entityResults',
                'selectedEntityId',
                'templateCollapsed',
                'cssCollapsed',
                'themeCollapsed',
                'tailwindCollapsed',
                'scheduleCollapsed',
                'customDataCollapsed',
                'storeId',
                'showEditScheduleModal',
                'editScheduleFrom',
                'editScheduleTo',
                'editScheduleOverrideLabel',
                'viewingDefault'
            ]);

            this.isInitialLoading(true);
            this.currentTemplateId('');
            this.currentTemplateStatus('');
            this.hasDraft(false);
            this.hasPublished(false);
            this.isOverrideActive(true);
            this.subject('');
            this.statusText('READY');
            this.statusCssClass('ete-status ete-status-ready');
            this.statusBarText('Editor initialized');
            this.showDraftBadge(false);
            this.showScheduleBadge(false);
            this.showExpiredBadge(false);
            this.tailwindCssOutput('No Tailwind CSS generated yet.');
            this.showCustomData(false);
            this.customDataJson('');
            this.selectedProvider('mock');
            this.providers([]);
            this.dataSourceLabel('Data Source');
            this.showEntitySearch(false);
            this.entitySearchQuery('');
            this.entityResults([]);
            this.selectedEntityId('');
            this.templateCollapsed(false);
            this.cssCollapsed(true);
            this.themeCollapsed(true);
            this.tailwindCollapsed(true);
            this.scheduleCollapsed(true);
            this.customDataCollapsed(false);
            this.showEditScheduleModal(false);
            this.editScheduleFrom('');
            this.editScheduleTo('');
            this.editScheduleOverrideLabel('');
            this.viewingDefault(false);

            this._editScheduleEntityId = null;

            this.observe([
                'confirmModalVisible',
                'confirmModalTitle',
                'confirmModalMessage',
                'confirmModalDetail',
                'confirmModalAction',
                'confirmModalType',
                'sendTestEmailVisible',
                'sendTestEmailAddress',
                'sendTestEmailFeedback',
                'sendTestEmailHasError',
                'sendTestEmailSending'
            ]);

            this.sendTestEmailVisible(false);
            this.sendTestEmailAddress('');
            this.sendTestEmailFeedback('');
            this.sendTestEmailHasError(false);
            this.sendTestEmailSending(false);

            this.confirmModalVisible(false);
            this.confirmModalTitle('');
            this.confirmModalMessage('');
            this.confirmModalDetail('');
            this.confirmModalAction('');
            this.confirmModalType('danger');
            this._confirmCallback = null;

            this._initSubscriptions();
            this._initKeyboardShortcuts();
            this._loadInitialData();

            return this;
        },

        /**
         * Subscribe to child component events and observable changes
         * to wire up inter-component communication.
         */
        _initSubscriptions: function () {
            var self = this;

            registry.get(this.name + '.templateSidebar', function (sidebar) {
                var debouncedLoadTemplate = _.debounce(function (identifier, entityId) {
                    self.loadTemplate(identifier, entityId);
                }, 300);

                self.templateSidebar = sidebar;

                sidebar.on('templateSelect', function (identifier) {
                    self.viewingDefault(true);
                    debouncedLoadTemplate(identifier);
                });

                sidebar.on('overrideSelect', function (data) {
                    self.viewingDefault(false);
                    debouncedLoadTemplate(
                        data.template.id,
                        data.override.entity_id
                    );
                });

                sidebar.on('createDraft', function (identifier) {
                    self.createNewDraft(identifier);
                });

                sidebar.on('editSchedule', function (data) {
                    self.openEditScheduleModal(data.override, data.template);
                });

                sidebar.on('confirmAction', function (params) {
                    self.showConfirm(params);
                });
            });

            registry.get(this.name + '.templateEditor', function (editor) {
                self.templateEditor = editor;

                editor.on('contentChange', function () {
                    self.onContentChange();
                });
            });

            registry.get(this.name + '.customCssEditor', function (cssEditor) {
                self.customCssEditor = cssEditor;

                self.cssCollapsed.subscribe(function (collapsed) {
                    if (!collapsed) {
                        setTimeout(function () {
                            cssEditor.refresh();
                        }, 50);
                    }
                });
            });

            registry.get(this.name + '.themeEditor', function (themeEditor) {
                self.themeEditor = themeEditor;

                self.themeCollapsed.subscribe(function (collapsed) {
                    if (!collapsed) {
                        setTimeout(function () {
                            themeEditor.refresh();
                        }, 50);
                    }
                });

                $('body').on('themeChange', function () {
                    self.schedulePreview();
                });
            });

            registry.get(this.name + '.previewPanel', function (preview) {
                self.previewPanel = preview;
            });

            registry.get(this.name + '.schedulePanel', function (schedule) {
                self.schedulePanel = schedule;

                schedule.on('scheduleChange', function (data) {
                    self._editScheduleEntityId = self._currentEntityId || null;
                    self.editScheduleFrom(data.active_from || '');
                    self.editScheduleTo(data.active_to || '');
                    self.applyEditSchedule();
                });
            });

            registry.get(this.name + '.publishDialog', function (dialog) {
                self.publishDialog = dialog;
            });

            registry.get(this.name + '.versionHistory', function (history) {
                self.versionHistory = history;

                history.on('historyPreviewStart', function () {
                    if (self.previewPanel) {
                        self.previewPanel.showLoading();
                    }
                });

                history.on('historyPreview', function (res) {
                    if (self.previewPanel) {
                        self.previewPanel.hideLoading();

                        if (res.success) {
                            self.previewPanel.setContent(res.html);
                        }
                    }
                });

                history.on('historyRestore', function (res) {
                    if (res.success) {
                        if (res.content !== undefined && self.templateEditor) {
                            self.templateEditor.setValue(res.content);
                        }

                        if (res.subject !== undefined) {
                            self.subject(res.subject || '');
                        }

                        self.currentTemplateStatus('draft');
                        self.hasDraft(true);
                        self._currentEntityId = res.entity_id || self._currentEntityId;
                        self.updateBadges();
                        self.setStatus('modified', 'REVERTED');

                        if (self.templateSidebar) {
                            self.templateSidebar.refresh();
                        }

                        self.schedulePreview();

                        setTimeout(function () {
                            self.setStatus('ready');
                        }, 2000);
                    }
                });

                history.on('historyClose', function () {
                    self.renderPreview();
                });

                history.on('confirmAction', function (params) {
                    self.showConfirm(params);
                });
            });

            registry.get(this.name + '.variableChooser', function (chooser) {
                self.variableChooser = chooser;

                chooser.on('insertVariable', function (variableValue) {
                    if (self.templateEditor) {
                        self.templateEditor.insertAtCursor(variableValue);
                    }
                });
            });

            registry.get(this.name + '.draftManager', function (manager) {
                self.draftManager = manager;
            });

            registry.get(this.name + '.draftListPanel', function (draftList) {
                self.draftListPanel = draftList;

                draftList.on('draftSelect', function (draftData) {
                    self.loadTemplate(
                        self.currentTemplateId(),
                        draftData.entity_id
                    );
                });

                draftList.on('draftCreate', function () {
                    self.createNewDraft(self.currentTemplateId());
                });

                draftList.on('confirmAction', function (params) {
                    self.showConfirm(params);
                });
            });

            registry.get(this.name + '.moreMenu', function (menu) {
                self.moreMenu = menu;

                menu.on('menuAction', function (action) {
                    switch (action) {
                        case 'previewInNewTab':
                            self.previewInNewTab();
                            break;
                        case 'openVersionHistory':
                            self.openVersionHistory();
                            break;
                        case 'deleteDraft':
                            self.discardDraft();
                            break;
                        case 'resetTemplate':
                            self.resetTemplate();
                            break;
                    }
                });
            });

            this.subject.subscribe(function () {
                if (!self._suppressChangeEvents) {
                    self.onContentChange();
                }
            });

            this.customDataJson.subscribe(function () {
                if (!self._suppressChangeEvents) {
                    self.schedulePreview();
                }
            });

            this.selectedProvider.subscribe(function () {
                self._updateProviderUI();

                if (!self._suppressChangeEvents) {
                    self.schedulePreview();
                }
            });

            this.entitySearchQuery.subscribe(function (query) {
                self.selectedEntityId('');

                if (self._entitySearchTimer) {
                    clearTimeout(self._entitySearchTimer);
                }

                self._entitySearchTimer = setTimeout(function () {
                    self.searchEntities(query);
                }, 400);
            });

            this.storeId.subscribe(function () {
                self.renderPreview();
            });
        },

        /**
         * Bind global keyboard shortcuts for the editor.
         */
        _initKeyboardShortcuts: function () {
            var self = this;

            this._keyboardHandler = function (e) {
                var tag = (e.target.tagName || '').toLowerCase(),
                    isInput = tag === 'input' || tag === 'textarea' || tag === 'select',
                    isCtrl = e.ctrlKey || e.metaKey;

                // Escape — close any open modal/panel
                if (e.key === 'Escape') {
                    if (self.confirmModalVisible()) {
                        self.cancelConfirm();
                        e.preventDefault();

                        return;
                    }

                    if (self.sendTestEmailVisible()) {
                        self.closeSendTestEmailDialog();
                        e.preventDefault();

                        return;
                    }

                    if (self.publishDialog && self.publishDialog.isVisible()) {
                        self.publishDialog.close();
                        e.preventDefault();

                        return;
                    }

                    if (self.versionHistory && self.versionHistory.isVisible()) {
                        self.versionHistory.close();
                        e.preventDefault();

                        return;
                    }

                    if (self.showEditScheduleModal()) {
                        self.closeEditScheduleModal();
                        e.preventDefault();

                        return;
                    }

                    if (self.variableChooser && self.variableChooser.isOpen && self.variableChooser.isOpen()) {
                        self.variableChooser.close();
                        e.preventDefault();

                        return;
                    }

                    if (self.moreMenu && self.moreMenu.isOpen()) {
                        self.moreMenu.close();
                        e.preventDefault();

                        return;
                    }

                    return;
                }

                // Ctrl+S — Save Draft
                if (isCtrl && e.key === 's' && !e.shiftKey) {
                    e.preventDefault();

                    if (self.currentTemplateId()) {
                        self.saveDraft();
                    }

                    return;
                }

                // Ctrl+Shift+P — Publish
                if (isCtrl && e.shiftKey && (e.key === 'p' || e.key === 'P')) {
                    e.preventDefault();

                    if (self.currentTemplateId()) {
                        self.openPublishDialog();
                    }

                    return;
                }

                // Ctrl+Shift+E — Send Test Email
                if (isCtrl && e.shiftKey && (e.key === 'e' || e.key === 'E')) {
                    e.preventDefault();

                    if (self.currentTemplateId()) {
                        self.openSendTestEmailDialog();
                    }

                    return;
                }

                // Ctrl+Enter — Refresh Preview (works even in inputs)
                if (isCtrl && e.key === 'Enter') {
                    e.preventDefault();
                    self.renderPreview();

                    return;
                }

                // Ctrl+Shift+H — Version History
                if (isCtrl && e.shiftKey && (e.key === 'h' || e.key === 'H')) {
                    e.preventDefault();
                    self.openVersionHistory();

                    return;
                }
            };

            $(document).on('keydown.eteShortcuts', this._keyboardHandler);
        },

        /**
         * Load initial data: sidebar templates and sample data providers.
         */
        _loadInitialData: function () {
            var self = this;

            this.loadSampleDataProviders();
            tailwindCompiler.init();

            registry.get(this.name + '.templateSidebar', function (sidebar) {
                sidebar.load().done(function () {
                    self.isInitialLoading(false);

                    if (self.selectedTemplate) {
                        sidebar.select(self.selectedTemplate);
                    }
                }).fail(function () {
                    self.isInitialLoading(false);
                });
            });
        },

        /**
         * Set schedule dates, resolving the panel via registry if needed.
         *
         * @param {string} from
         * @param {string} to
         */
        _setScheduleDates: function (from, to) {
            var self = this;

            if (this.schedulePanel) {
                this.schedulePanel.setDates(from, to);

                return;
            }

            registry.get(this.name + '.schedulePanel', function (panel) {
                self.schedulePanel = panel;
                panel.setDates(from, to);
            });
        },

        /**
         * Set the status indicator text and CSS class.
         *
         * @param {string} status
         * @param {string} [text]
         */
        setStatus: function (status, text) {
            this.statusCssClass('ete-status ete-status-' + status);
            this.statusText(text || status.toUpperCase());
        },

        /**
         * Update the draft, schedule, and expired badges based on current state.
         */
        updateBadges: function () {
            var scheduleStatus = this.schedulePanel
                ? this.schedulePanel.getStatus()
                : 'none';

            this.showDraftBadge(
                this.currentTemplateStatus() === 'draft' || this.hasDraft()
            );
            this.showScheduleBadge(scheduleStatus === 'scheduled');
            this.showExpiredBadge(scheduleStatus === 'expired');
        },

        /**
         * Get the effective store ID from the store view observable.
         *
         * @return {number}
         */
        getEffectiveStoreId: function () {
            return parseInt(this.storeId(), 10) || 0;
        },

        /**
         * Perform an AJAX request with form_key and store_id injected.
         *
         * @param {string} url
         * @param {Object} [data]
         * @param {string} [method]
         * @return {jQuery.Deferred}
         */
        _ajax: function (url, data, method) {
            var self = this,
                xhr;

            data = data || {};
            data.form_key = this.formKey;
            data.store_id = this.getEffectiveStoreId();

            xhr = $.ajax({
                url: url,
                type: method || 'GET',
                data: data,
                dataType: 'json',
                cache: false
            });

            this._pendingRequests.push(xhr);

            xhr.always(function () {
                var idx = self._pendingRequests.indexOf(xhr);

                if (idx !== -1) {
                    self._pendingRequests.splice(idx, 1);
                }
            });

            return xhr;
        },

        /**
         * Abort all in-flight AJAX requests.
         */
        _abortPendingRequests: function () {
            var pending = this._pendingRequests.slice();

            this._pendingRequests = [];

            pending.forEach(function (xhr) {
                if (xhr && xhr.readyState !== 4) {
                    xhr.abort();
                }
            });
        },

        /**
         * Load the list of available sample data providers from the server
         * and populate the providers observable.
         *
         * @param {string} [templateIdentifier]
         */
        loadSampleDataProviders: function (templateIdentifier) {
            var self = this,
                data = {};

            if (templateIdentifier) {
                data.template_identifier = templateIdentifier;
            }

            this._ajax(this.urls.sampleDataLoadList, data).done(function (res) {
                var items = [];

                if (res.success && res.providers) {
                    $.each(res.providers, function (i, p) {
                        items.push({
                            code: p.code,
                            label: p.label
                        });
                        self._providerEntitySelectionMap[p.code] =
                            p.supports_entity_search || false;
                    });
                }

                if (res.data_source_label) {
                    self.dataSourceLabel(res.data_source_label);
                }

                items.push({code: 'custom', label: 'Custom Data'});
                self._providerEntitySelectionMap['custom'] = false;

                self.providers(items);

                if (items.length > 0 && !self.selectedProvider()) {
                    self.selectedProvider(items[0].code);
                }
            });
        },

        /**
         * Update the entity search visibility and custom data visibility
         * based on the currently selected provider.
         */
        _updateProviderUI: function () {
            var providerCode = this.selectedProvider() || '',
                requiresEntity = this._providerEntitySelectionMap[providerCode] || false,
                isCustom = providerCode === 'custom';

            this.showEntitySearch(requiresEntity);
            this.showCustomData(isCustom);

            if (!requiresEntity) {
                this.selectedEntityId('');
                this.entitySearchQuery('');
                this.entityResults([]);
            }
        },

        /**
         * Search for entities matching the given query for the selected provider.
         *
         * @param {string} query
         */
        searchEntities: function (query) {
            var self = this,
                providerCode = this.selectedProvider() || '';

            if (!query || query.length < 2) {
                this.entityResults([]);

                return;
            }

            this._ajax(this.urls.sampleDataSearchEntities, {
                provider_code: providerCode,
                query: query
            }).done(function (res) {
                if (res.success && res.results && res.results.length > 0) {
                    self.entityResults(res.results);
                } else {
                    self.entityResults([{
                        id: '',
                        label: 'No results found'
                    }]);
                }
            });
        },

        /**
         * Select an entity from the search results and trigger a preview.
         *
         * @param {Object} entityData
         */
        selectEntity: function (entityData) {
            if (!entityData.id) {
                this.entityResults([]);

                return;
            }

            this.selectedEntityId(String(entityData.id));
            this.entitySearchQuery(entityData.label);
            this.entityResults([]);
            this.renderPreview();
        },

        /**
         * Load a template by its identifier, optionally loading a specific draft.
         *
         * @param {string} identifier
         * @param {string|number} [entityId]
         */
        loadTemplate: function (identifier, entityId) {
            var self = this,
                requestData;

            if (!identifier) {
                return $.Deferred().reject();
            }

            this._abortPendingRequests();
            this.setStatus('saving', 'LOADING');
            this.currentTemplateId(identifier);
            this._currentEntityId = entityId || null;

            this._setScheduleDates('', '');

            requestData = {
                template_identifier: identifier
            };

            if (entityId) {
                requestData.entity_id = entityId;
            }

            if (this.viewingDefault()) {
                requestData.default_only = 1;
            }

            this._loadRequestId = identifier + ':' + (entityId || '');

            return this._ajax(this.urls.load, requestData).done(function (res) {
                if (self._loadRequestId !== identifier + ':' + (entityId || '')) {
                    return;
                }

                if (res.success && res.template) {
                    var isDefault = self.viewingDefault();

                    self._suppressChangeEvents = true;

                    if (self.templateEditor) {
                        self.templateEditor.setValue(
                            isDefault
                                ? (res.template.default_content || '')
                                : (res.template.content || '')
                        );
                    }

                    self.subject(
                        isDefault
                            ? (res.template.default_subject || '')
                            : (res.template.subject || '')
                    );

                    if (self.customCssEditor) {
                        self.customCssEditor.setValue(isDefault ? '' : (res.template.custom_css || ''));
                    }

                    self.currentTemplateStatus(res.template.status || '');
                    self.hasDraft(res.template.has_draft || false);
                    self.hasPublished(res.template.has_published || false);
                    self._publishedEntityId = res.template.published
                        ? res.template.published.entity_id
                        : null;
                    self.isOverrideActive(
                        res.template.published
                            ? res.template.published.is_active !== false
                            : true
                    );
                    self._currentEntityId = isDefault ? null : (res.template.entity_id || null);

                    if (!isDefault && res.template.tailwind_css) {
                        self._lastTailwindCss = res.template.tailwind_css;
                        self.tailwindCssOutput(res.template.tailwind_css);
                    } else if (isDefault) {
                        self._lastTailwindCss = '';
                        self.tailwindCssOutput('');
                    }

                    self._suppressChangeEvents = false;

                    self._setScheduleDates(
                        isDefault ? '' : (res.template.active_from || ''),
                        isDefault ? '' : (res.template.active_to || '')
                    );

                    if (self.draftListPanel && res.template.drafts) {
                        self.draftListPanel.setDrafts(res.template.drafts);

                        if (self._currentEntityId) {
                            self.draftListPanel.activeDraftId(self._currentEntityId);
                        }
                    }

                    self.updateBadges();
                    self.setStatus('ready');

                    if (self.draftManager) {
                        self.draftManager.markClean();
                    }

                    self.statusBarText(
                        isDefault
                            ? 'Default template: ' + identifier
                            : 'Template loaded: ' + identifier
                    );
                    self.renderPreview();
                    self.loadSampleDataProviders(identifier);
                } else {
                    self.setStatus('error', 'ERROR');
                    self.statusBarText('Failed to load template');
                }
            }).fail(function () {
                self.setStatus('error', 'ERROR');
                self.statusBarText('Failed to load template');
            });
        },

        /**
         * Render the preview. Sends preview request immediately using
         * cached Tailwind CSS, and recompiles Tailwind in the background.
         */
        renderPreview: function () {
            var self = this,
                content = this.templateEditor ? this.templateEditor.getValue() : '';

            if (!content) {
                return;
            }

            if (this.previewPanel) {
                this.previewPanel.showLoading();
            }

            this._sendPreviewRequest(content);

            tailwindCompiler.compile(content).done(function (twCss) {
                if (twCss && twCss !== self._lastTailwindCss) {
                    self._lastTailwindCss = twCss;
                    self.tailwindCssOutput(twCss);
                    self._sendPreviewRequest(content);
                }
            });
        },

        /**
         * Send the preview AJAX request to the server.
         *
         * @param {string} content
         * @private
         */
        _sendPreviewRequest: function (content) {
            var self = this,
                providerCode = this.selectedProvider() || 'mock',
                data;

            data = {
                template_content: content,
                theme_json: this.themeEditor ? this.themeEditor.getThemeJson() : '',
                custom_css: this.customCssEditor ? this.customCssEditor.getValue() : '',
                tailwind_css: this._lastTailwindCss || '',
                provider_code: providerCode,
                template_identifier: this.currentTemplateId(),
                entity_id: this.selectedEntityId()
            };

            if (providerCode === 'custom') {
                data.custom_variables = this.customDataJson() || '';
            }

            this._ajax(this.urls.preview, data, 'POST').done(function (res) {
                if (!self.previewPanel) {
                    return;
                }

                self.previewPanel.hideLoading();

                if (res.success) {
                    self.previewPanel.setContent(res.html);
                } else {
                    self.previewPanel.setContent(
                        '<div style="color:#eb5202;padding:20px;">Error: ' +
                        (res.message || 'Unknown error') + '</div>'
                    );
                }
            }).fail(function () {
                if (self.previewPanel) {
                    self.previewPanel.hideLoading();
                    self.previewPanel.setContent(
                        '<div style="color:#eb5202;padding:20px;">Failed to render preview.</div>'
                    );
                }
            });
        },

        /**
         * Schedule a debounced preview render after 800ms.
         */
        schedulePreview: function () {
            var self = this;

            if (this._previewDebounceTimer) {
                clearTimeout(this._previewDebounceTimer);
            }

            this._previewDebounceTimer = setTimeout(function () {
                self.renderPreview();
            }, 800);
        },

        /**
         * Handle content changes from template editor, CSS editor, or subject field.
         */
        onContentChange: function () {
            if (this._suppressChangeEvents) {
                return;
            }

            this.setStatus('modified');

            if (this.draftManager) {
                this.draftManager.markDirty();
            }

            this.schedulePreview();
        },

        /**
         * Collect the current state of all editors into a single data object for saving.
         *
         * @return {Object}
         */
        getSaveData: function () {
            var dates = this.schedulePanel
                ? this.schedulePanel.getDates()
                : {active_from: '', active_to: ''};

            return {
                template_identifier: this.currentTemplateId(),
                template_content: this.templateEditor
                    ? this.templateEditor.getValue()
                    : '',
                template_subject: this.subject(),
                custom_css: this.customCssEditor
                    ? this.customCssEditor.getValue()
                    : '',
                tailwind_css: this._lastTailwindCss || '',
                theme_id: this.themeEditor
                    ? this.themeEditor.getCurrentThemeId()
                    : '',
                active_from: dates.active_from,
                active_to: dates.active_to,
                entity_id: this._currentEntityId || ''
            };
        },

        /**
         * Save the current editor state as a draft.
         *
         * @param {boolean} [asNew] - When true, save as a new draft instead of updating the current one.
         */
        saveDraft: function (asNew) {
            var self = this,
                data;

            if (!this.currentTemplateId() || this.viewingDefault()) {
                return;
            }

            this.setStatus('saving', 'SAVING DRAFT');

            data = this.getSaveData();
            data.status = 'draft';

            if (asNew) {
                delete data.entity_id;
            }

            this._ajax(this.urls.saveDraft, data, 'POST').done(function (res) {
                if (res.success) {
                    self.currentTemplateStatus('draft');
                    self.hasDraft(true);
                    self._currentEntityId = res.entity_id || self._currentEntityId;
                    self.updateBadges();
                    self.setStatus('ready', 'DRAFT SAVED');

                    if (self.templateSidebar) {
                        self.templateSidebar.markDraft(self.currentTemplateId(), true);
                    }

                    if (self.draftManager) {
                        self.draftManager.markClean();
                        self.draftManager.updateSavedTime();
                    }

                    if (self.draftListPanel && res.drafts) {
                        self.draftListPanel.setDrafts(res.drafts);
                    }

                    self.statusBarText('Draft saved');

                    setTimeout(function () {
                        self.setStatus('ready');
                    }, 2000);
                } else {
                    self.setStatus('error', 'ERROR');
                    self.statusBarText(res.message || 'Failed to save draft');
                    uiAlert({
                        title: $.mage.__('Save Failed'),
                        content: res.message || $.mage.__('An error occurred while saving the draft.')
                    });
                }
            }).fail(function () {
                self.setStatus('error', 'ERROR');
                self.statusBarText('Failed to save draft');
                uiAlert({
                    title: $.mage.__('Save Failed'),
                    content: $.mage.__('A network error occurred while saving the draft. Please try again.')
                });
            });
        },

        /**
         * Create a new draft for the given template identifier.
         * Loads the template first, then saves as a brand-new draft with force_new flag.
         *
         * @param {string} identifier
         */
        createNewDraft: function (identifier) {
            var self = this;

            this.loadTemplate(identifier).done(function (res) {
                if (!res || !res.success) {
                    return;
                }

                if (res.template.default_content !== undefined && self.templateEditor) {
                    self.templateEditor.setValue(res.template.default_content || '');
                }

                if (res.template.default_subject !== undefined) {
                    self.subject(res.template.default_subject || '');
                }

                self.setStatus('saving', 'CREATING DRAFT');

                var data = self.getSaveData();

                data.status = 'draft';
                data.force_new = 1;
                data.draft_name = res.template.label || identifier;
                delete data.entity_id;

                self._ajax(self.urls.saveDraft, data, 'POST').done(function (saveRes) {
                    if (saveRes.success) {
                        self.currentTemplateStatus('draft');
                        self.hasDraft(true);
                        self._currentEntityId = saveRes.entity_id;
                        self.updateBadges();
                        self.setStatus('ready', 'DRAFT CREATED');

                        if (self.templateSidebar) {
                            self.templateSidebar.refresh().done(function () {
                                self.templateSidebar.activeOverrideId(saveRes.entity_id);
                                self.templateSidebar.expandTemplate(identifier);
                            });
                        }

                        if (self.draftManager) {
                            self.draftManager.markClean();
                            self.draftManager.updateSavedTime();
                        }

                        self.statusBarText('New draft created');

                        setTimeout(function () {
                            self.setStatus('ready');
                        }, 2000);
                    } else {
                        self.setStatus('error', 'ERROR');
                        self.statusBarText(saveRes.message || 'Failed to create draft');
                    }
                }).fail(function () {
                    self.setStatus('error', 'ERROR');
                    self.statusBarText('Failed to create draft');
                });
            });
        },

        /**
         * Publish the current template. Saves a draft first, then publishes it.
         *
         * @param {string} [comment]
         */
        publishTemplate: function (comment) {
            var self = this,
                data;

            if (!this.currentTemplateId()) {
                return;
            }

            this.setStatus('saving', 'PUBLISHING');

            data = this.getSaveData();
            data.status = 'draft';

            this._ajax(this.urls.saveDraft, data, 'POST').done(function (saveRes) {
                if (!saveRes.success || !saveRes.entity_id) {
                    self.setStatus('error', 'ERROR');
                    self.statusBarText('Failed to save before publishing');

                    return;
                }

                self._ajax(self.urls.publish, {
                    entity_id: saveRes.entity_id,
                    version_comment: comment || ''
                }, 'POST').done(function (res) {
                    if (res.success) {
                        self.currentTemplateStatus('published');
                        self.hasDraft(false);
                        self.hasPublished(true);
                        self._currentEntityId = res.entity_id || null;
                        self.updateBadges();
                        self.setStatus('ready', 'PUBLISHED');

                        if (self.templateSidebar) {
                            self.templateSidebar.refresh();
                        }

                        if (self.draftManager) {
                            self.draftManager.markClean();
                        }

                        self.statusBarText('Template published successfully');

                        setTimeout(function () {
                            self.setStatus('ready');
                        }, 2000);
                    } else {
                        self.setStatus('error', 'ERROR');
                        self.statusBarText(res.message || 'Publish failed');
                        uiAlert({
                            title: $.mage.__('Publish Failed'),
                            content: res.message || $.mage.__('An error occurred while publishing the template.')
                        });
                    }
                }).fail(function () {
                    self.setStatus('error', 'ERROR');
                    self.statusBarText('Failed to publish template');
                    uiAlert({
                        title: $.mage.__('Publish Failed'),
                        content: $.mage.__('A network error occurred while publishing the template. Please try again.')
                    });
                });
            }).fail(function () {
                self.setStatus('error', 'ERROR');
                self.statusBarText('Failed to save before publishing');
                uiAlert({
                    title: $.mage.__('Publish Failed'),
                    content: $.mage.__('Failed to save the draft before publishing. Please try again.')
                });
            });
        },

        /**
         * Discard the current draft after user confirmation.
         */
        discardDraft: function () {
            var self = this;

            if (!this.currentTemplateId()) {
                return;
            }

            this.showConfirm({
                title: $.mage.__('Discard Draft'),
                message: $.mage.__('Are you sure you want to discard this draft? The published version will remain active.'),
                detail: '<strong>' + self.currentTemplateId() + '</strong>',
                actionLabel: $.mage.__('Discard'),
                type: 'danger',
                onConfirm: function () {
                    self.setStatus('saving', 'DISCARDING');

                    self._ajax(self.urls.deleteDraft, {
                        template_identifier: self.currentTemplateId(),
                        entity_id: self._currentEntityId || ''
                    }, 'POST').done(function (res) {
                        if (res.success) {
                            self.hasDraft(false);
                            self._currentEntityId = null;

                            if (self.templateSidebar) {
                                self.templateSidebar.refresh();
                            }

                            self.statusBarText('Draft discarded');
                            self.loadTemplate(self.currentTemplateId());
                        } else {
                            self.setStatus('error', 'ERROR');
                            self.statusBarText(res.message || 'Failed to discard draft');
                        }
                    }).fail(function () {
                        self.setStatus('error', 'ERROR');
                        self.statusBarText('Failed to discard draft');
                    });
                }
            });
        },

        /**
         * Reset the template to the Magento default after user confirmation.
         */
        resetTemplate: function () {
            var self = this;

            if (!this.currentTemplateId()) {
                return;
            }

            this.showConfirm({
                title: $.mage.__('Reset to Default'),
                message: $.mage.__('This will permanently remove all customizations for this template and revert to the Magento default. This cannot be undone.'),
                detail: '<strong>' + self.currentTemplateId() + '</strong>',
                actionLabel: $.mage.__('Reset to Default'),
                type: 'danger',
                onConfirm: function () {
                    self.setStatus('saving', 'RESETTING');

                    self._ajax(self.urls.reset, {
                        template_identifier: self.currentTemplateId()
                    }, 'POST').done(function (res) {
                        if (res.success) {
                            self.currentTemplateStatus('');
                            self.hasDraft(false);
                            self.hasPublished(false);
                            self._currentEntityId = null;

                            if (self.schedulePanel) {
                                self.schedulePanel.clearDates();
                            }

                            self.updateBadges();

                            if (self.templateSidebar) {
                                self.templateSidebar.refresh();
                            }

                            self.statusBarText('Template reset to default');
                            self.loadTemplate(self.currentTemplateId());
                        } else {
                            self.setStatus('error', 'ERROR');
                            self.statusBarText(res.message || 'Failed to reset template');
                        }
                    }).fail(function () {
                        self.setStatus('error', 'ERROR');
                        self.statusBarText('Failed to reset template');
                    });
                }
            });
        },

        /**
         * Close the current draft and reload the template without draft context.
         */
        closeDraft: function () {
            this._currentEntityId = null;
            this.loadTemplate(this.currentTemplateId());
        },

        /**
         * Open the edit schedule modal for a published override from the sidebar.
         *
         * @param {Object} overrideData
         * @param {Object} templateData
         */
        openEditScheduleModal: function (overrideData, templateData) {
            this._editScheduleEntityId = overrideData.entity_id;
            this.editScheduleFrom(overrideData.active_from || '');
            this.editScheduleTo(overrideData.active_to || '');
            this.editScheduleOverrideLabel(overrideData.label || templateData.label || '');
            this.showEditScheduleModal(true);
            this._initEditScheduleCalendars();
        },

        /**
         * Close the edit schedule modal.
         */
        closeEditScheduleModal: function () {
            this.showEditScheduleModal(false);
            this._editScheduleEntityId = null;
        },

        /**
         * Apply the schedule from the edit schedule modal and save via AJAX.
         */
        applyEditSchedule: function () {
            var self = this,
                $modal = $('.ete-edit-schedule-modal:visible'),
                fromVal = $modal.find('.ete-edit-schedule-from').val(),
                toVal = $modal.find('.ete-edit-schedule-to').val(),
                fromTime, toTime;

            if (fromVal !== undefined) {
                this.editScheduleFrom(fromVal);
            }

            if (toVal !== undefined) {
                this.editScheduleTo(toVal);
            }

            if (this.editScheduleFrom() && this.editScheduleTo()) {
                fromTime = new Date(this.editScheduleFrom().replace(' ', 'T')).getTime();
                toTime = new Date(this.editScheduleTo().replace(' ', 'T')).getTime();

                if (fromTime >= toTime) {
                    uiAlert({
                        title: $.mage.__('Invalid Date Range'),
                        content: $.mage.__('Active From must be before Active To.')
                    });

                    return;
                }
            }

            this.showEditScheduleModal(false);
            this.setStatus('saving', 'UPDATING SCHEDULE');

            this._ajax(this.urls.updateSchedule, {
                entity_id: this._editScheduleEntityId,
                active_from: this.editScheduleFrom(),
                active_to: this.editScheduleTo()
            }, 'POST').done(function (res) {
                if (res.success) {
                    self.setStatus('ready', 'SCHEDULE UPDATED');
                    self.statusBarText('Schedule updated successfully');

                    if (self.templateSidebar) {
                        self.templateSidebar.refresh();
                    }

                    if (self._currentEntityId && String(self._currentEntityId) === String(self._editScheduleEntityId)) {
                        if (self.schedulePanel) {
                            self.schedulePanel.setDates(res.active_from || '', res.active_to || '');
                            self.updateBadges();
                        }
                    }

                    self._editScheduleEntityId = null;

                    setTimeout(function () {
                        self.setStatus('ready');
                    }, 2000);
                } else {
                    self.setStatus('error', 'ERROR');
                    self.statusBarText(res.message || 'Failed to update schedule');
                    uiAlert({
                        title: $.mage.__('Schedule Update Failed'),
                        content: res.message || $.mage.__('Failed to update schedule.')
                    });
                    self._editScheduleEntityId = null;
                }
            }).fail(function () {
                self.setStatus('error', 'ERROR');
                self.statusBarText('Failed to update schedule');
                uiAlert({
                    title: $.mage.__('Error'),
                    content: $.mage.__('Failed to update schedule. Please try again.')
                });
                self._editScheduleEntityId = null;
            });
        },

        /**
         * Remove the schedule from the edit schedule modal and save via AJAX.
         */
        removeEditSchedule: function () {
            this.editScheduleFrom('');
            this.editScheduleTo('');

            var $modal = $('.ete-edit-schedule-modal:visible');

            $modal.find('.ete-edit-schedule-from').val('');
            $modal.find('.ete-edit-schedule-to').val('');
            this.applyEditSchedule();
        },

        /**
         * Initialize calendar widgets on the edit schedule modal inputs.
         */
        _initEditScheduleCalendars: function () {
            var self = this;

            setTimeout(function () {
                var $fromInput = $('.ete-edit-schedule-from:visible'),
                    $toInput = $('.ete-edit-schedule-to:visible'),
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
                        self.editScheduleFrom($(this).val());
                    });
                }

                if ($toInput.length && !$toInput.data('calendarInitialized')) {
                    $toInput.calendar(calendarOpts);
                    $toInput.data('calendarInitialized', true);

                    $toInput.on('change', function () {
                        self.editScheduleTo($(this).val());
                    });
                }
            }, 150);
        },

        /**
         * Open the publish dialog with the current template name and store view.
         */
        /**
         * Show a styled confirmation dialog.
         *
         * @param {Object} params
         * @param {string} params.title
         * @param {string} params.message
         * @param {string} [params.detail]
         * @param {string} [params.actionLabel]
         * @param {string} [params.type]
         * @param {Function} params.onConfirm
         */
        showConfirm: function (params) {
            this.confirmModalTitle(params.title || '');
            this.confirmModalMessage(params.message || '');
            this.confirmModalDetail(params.detail || '');
            this.confirmModalAction(params.actionLabel || $.mage.__('Confirm'));
            this.confirmModalType(params.type || 'danger');
            this._confirmCallback = params.onConfirm || null;
            this.confirmModalVisible(true);
        },

        /**
         * Accept the confirmation and execute the callback.
         */
        acceptConfirm: function () {
            var cb = this._confirmCallback;

            this.confirmModalVisible(false);
            this._confirmCallback = null;

            if (typeof cb === 'function') {
                cb();
            }
        },

        /**
         * Cancel the confirmation dialog.
         */
        cancelConfirm: function () {
            this.confirmModalVisible(false);
            this._confirmCallback = null;
        },

        /**
         * Open the send test email dialog.
         */
        openSendTestEmailDialog: function () {
            this.sendTestEmailFeedback('');
            this.sendTestEmailHasError(false);
            this.sendTestEmailSending(false);
            this.sendTestEmailVisible(true);
        },

        /**
         * Close the send test email dialog.
         */
        closeSendTestEmailDialog: function () {
            this.sendTestEmailVisible(false);
            this.sendTestEmailFeedback('');
            this.sendTestEmailHasError(false);
            this.sendTestEmailSending(false);
        },

        /**
         * Handle Enter key in the test email input.
         *
         * @param {Object} data
         * @param {Event} event
         * @return {boolean}
         */
        sendTestEmailKeydown: function (data, event) {
            if (event.key === 'Enter') {
                this.sendTestEmail();

                return false;
            }

            return true;
        },

        /**
         * Send the test email via AJAX.
         */
        sendTestEmail: function () {
            var self = this,
                email = (this.sendTestEmailAddress() || '').trim(),
                content,
                data;

            if (!email) {
                this.sendTestEmailFeedback($.mage.__('Please enter an email address.'));
                this.sendTestEmailHasError(true);

                return;
            }

            content = this.templateEditor ? this.templateEditor.getValue() : '';

            if (!content) {
                this.sendTestEmailFeedback($.mage.__('No template content to send.'));
                this.sendTestEmailHasError(true);

                return;
            }

            this.sendTestEmailSending(true);
            this.sendTestEmailFeedback('');
            this.sendTestEmailHasError(false);

            data = {
                recipient_email: email,
                template_content: content,
                template_subject: this.subject() || '',
                template_identifier: this.currentTemplateId(),
                custom_css: this.customCssEditor ? this.customCssEditor.getValue() : '',
                tailwind_css: this._lastTailwindCss || '',
                provider_code: this.selectedProvider() || 'mock',
                entity_id: this.selectedEntityId ? this.selectedEntityId() : ''
            };

            this._ajax(this.urls.sendTestEmail, data, 'POST').done(function (res) {
                self.sendTestEmailSending(false);

                if (res.success) {
                    self.sendTestEmailFeedback(res.message || $.mage.__('Test email sent successfully.'));
                    self.sendTestEmailHasError(false);
                    self.statusBarText(res.message || 'Test email sent');
                } else {
                    self.sendTestEmailFeedback(res.message || $.mage.__('Failed to send test email.'));
                    self.sendTestEmailHasError(true);
                }
            }).fail(function () {
                self.sendTestEmailSending(false);
                self.sendTestEmailFeedback($.mage.__('Network error. Please try again.'));
                self.sendTestEmailHasError(true);
            });
        },

        /**
         * Toggle the is_active flag on the current published override.
         */
        toggleActiveOverride: function () {
            var self = this,
                published = this._getPublishedEntityId();

            if (!published) {
                return;
            }

            this._ajax(this.urls.toggleActive, {entity_id: published}, 'POST').done(function (res) {
                if (res.success) {
                    self.isOverrideActive(res.is_active);
                    self.statusBarText(res.message || '');

                    if (self.templateSidebar) {
                        self.templateSidebar.refresh();
                    }
                }
            });
        },

        /**
         * Get the entity_id of the published override for the current template.
         *
         * @return {number|null}
         */
        _getPublishedEntityId: function () {
            return this._publishedEntityId || null;
        },

        openPublishDialog: function () {
            var self = this,
                dates = this.schedulePanel
                    ? this.schedulePanel.getDates()
                    : {active_from: '', active_to: ''},
                summary = this._computeChangesSummary(),
                storeName = this._getCurrentStoreName();

            if (this.publishDialog) {
                this.publishDialog.open({
                    activeFrom: dates.active_from,
                    activeTo: dates.active_to,
                    changesSummary: summary,
                    targetStore: storeName,
                    templateName: this.currentTemplateId(),
                    onPublish: function (comment, scheduleFrom, scheduleTo) {
                        if (self.schedulePanel && (scheduleFrom || scheduleTo)) {
                            self.schedulePanel.setDates(scheduleFrom, scheduleTo);
                        } else if (self.schedulePanel && !scheduleFrom && !scheduleTo) {
                            self.schedulePanel.clearDates();
                        }

                        self.updateBadges();
                        self.publishTemplate(comment);
                    }
                });
            }
        },

        /**
         * Compute a summary of changes for the publish dialog.
         *
         * @return {Array}
         */
        _computeChangesSummary: function () {
            var changes = [],
                content = this.templateEditor ? this.templateEditor.getValue() : '',
                subject = this.subject() || '',
                css = this.customCssEditor ? this.customCssEditor.getValue() : '',
                themeId = this.themeEditor ? this.themeEditor.getCurrentThemeId() : '';

            if (content) {
                changes.push({
                    type: 'template',
                    icon: '&#9998;',
                    label: $.mage.__('Template HTML'),
                    detail: content.length + ' ' + $.mage.__('characters')
                });
            }

            if (subject) {
                changes.push({
                    type: 'subject',
                    icon: '&#9993;',
                    label: $.mage.__('Subject Line'),
                    detail: '"' + (subject.length > 50 ? subject.substring(0, 50) + '...' : subject) + '"'
                });
            }

            if (css && css.trim()) {
                changes.push({
                    type: 'css',
                    icon: '&#127912;',
                    label: $.mage.__('Custom CSS'),
                    detail: css.trim().split('\n').length + ' ' + $.mage.__('lines')
                });
            }

            if (themeId) {
                changes.push({
                    type: 'theme',
                    icon: '&#9726;',
                    label: $.mage.__('Theme Applied'),
                    detail: ''
                });
            }

            if (this._lastTailwindCss) {
                changes.push({
                    type: 'tailwind',
                    icon: '&#9729;',
                    label: $.mage.__('Tailwind CSS'),
                    detail: $.mage.__('auto-generated')
                });
            }

            return changes;
        },

        /**
         * Get the name of the currently selected store view.
         *
         * @return {string}
         */
        _getCurrentStoreName: function () {
            var storeId = this.getEffectiveStoreId(),
                stores = this.stores || [],
                i;

            if (typeof stores === 'function') {
                stores = stores();
            }

            for (i = 0; i < stores.length; i++) {
                if (parseInt(stores[i].id, 10) === storeId) {
                    return stores[i].name;
                }
            }

            return '';
        },

        /**
         * Toggle the more actions dropdown menu.
         */
        toggleMoreMenu: function () {
            if (this.moreMenu) {
                this.moreMenu.toggle();
            }
        },

        /**
         * Close the more actions dropdown menu.
         */
        _closeMoreMenu: function () {
            if (this.moreMenu) {
                this.moreMenu.close();
            }
        },

        /**
         * Open the variable chooser panel.
         */
        openVariableChooser: function () {
            if (this.variableChooser) {
                if (this.variableChooser.isOpen()) {
                    this.variableChooser.close();
                } else {
                    this.variableChooser.open(
                        this.currentTemplateId(),
                        this.getEffectiveStoreId()
                    );
                }
            }
        },

        /**
         * Open the version history panel for the current template.
         */
        openVersionHistory: function () {
            this._closeMoreMenu();

            if (this.currentTemplateId() && this.versionHistory) {
                this.versionHistory.show(
                    this.currentTemplateId(),
                    this.getEffectiveStoreId()
                );
            }
        },

        /**
         * Reload the current template from the server.
         */
        reloadTemplate: function () {
            if (this.currentTemplateId()) {
                this.loadTemplate(
                    this.currentTemplateId(),
                    this._currentEntityId
                );
            }
        },

        /**
         * Reload drafts for the current template via the draft list panel.
         */
        loadDrafts: function () {
            if (this.draftListPanel && this.currentTemplateId()) {
                this.draftListPanel.loadDrafts(
                    this.currentTemplateId(),
                    this.getEffectiveStoreId()
                );
            }
        },

        /**
         * Open a preview of the current template in a new browser tab.
         */
        previewInNewTab: function () {
            var content, form, fields;

            this._closeMoreMenu();

            content = this.templateEditor ? this.templateEditor.getValue() : '';

            if (!content) {
                return;
            }

            form = document.createElement('form');
            form.method = 'POST';
            form.action = this.urls.preview;
            form.target = '_blank';

            fields = {
                template_content: content,
                theme_json: this.themeEditor ? this.themeEditor.getThemeJson() : '',
                custom_css: this.customCssEditor ? this.customCssEditor.getValue() : '',
                template_identifier: this.currentTemplateId(),
                form_key: this.formKey,
                store_id: this.getEffectiveStoreId(),
                raw: '1'
            };

            Object.keys(fields).forEach(function (key) {
                var input = document.createElement('input');

                input.type = 'hidden';
                input.name = key;
                input.value = fields[key];
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        },

        /**
         * Handle mousedown on the split-pane resizer to start drag resizing.
         *
         * @param {Object} data
         * @param {Event} event
         */
        startResize: function (data, event) {
            var self = this,
                leftPanel = $('#ete-panel-left'),
                startX = event.clientX,
                startWidth = leftPanel.width(),
                onMouseMove,
                onMouseUp;

            event.preventDefault();
            $('body').css('cursor', 'col-resize');

            onMouseMove = function (e) {
                var container = leftPanel.closest('.ete-panels-container'),
                    newWidth = startWidth + (e.clientX - startX),
                    minWidth = 300,
                    maxWidth = container.width() - 350;

                newWidth = Math.max(minWidth, Math.min(maxWidth, newWidth));
                leftPanel.css('width', newWidth + 'px');
            };

            onMouseUp = function () {
                $(document).off('mousemove.eteResize');
                $(document).off('mouseup.eteResize');
                $('body').css('cursor', '');

                if (self.templateEditor) {
                    self.templateEditor.refresh();
                }

                if (self.themeEditor) {
                    self.themeEditor.refresh();
                }

                if (self.customCssEditor) {
                    self.customCssEditor.refresh();
                }
            };

            $(document).on('mousemove.eteResize', onMouseMove);
            $(document).on('mouseup.eteResize', onMouseUp);
        },

        /**
         * Handle clicks on the document to close menus when clicking outside.
         *
         * @param {Object} data
         * @param {Event} event
         * @return {boolean}
         */
        handleDocumentClick: function (data, event) {
            var target = $(event.target);

            if (!target.closest('.ete-more-menu, .ete-toolbar-action-more').length) {
                this._closeMoreMenu();
            }

            if (!target.closest('.ete-entity-search').length) {
                this.entityResults([]);
            }

            return true;
        },

        /**
         * Clean up timers and subscriptions when the component is destroyed.
         */
        destroy: function () {
            if (this._previewDebounceTimer) {
                clearTimeout(this._previewDebounceTimer);
                this._previewDebounceTimer = null;
            }

            if (this._entitySearchTimer) {
                clearTimeout(this._entitySearchTimer);
                this._entitySearchTimer = null;
            }

            $(document).off('keydown.eteShortcuts');

            tailwindCompiler.destroy();

            this._super();
        }
    });
});
