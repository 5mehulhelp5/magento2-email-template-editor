<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterface;
use Hryvinskyi\EmailTemplateEditor\Api\PluginBypassFlagInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateAreaResolverInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateLoaderInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateOverrideRepositoryInterface;
use Magento\Email\Model\Template\Config as EmailConfig;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Psr\Log\LoggerInterface;

class TemplateLoader implements TemplateLoaderInterface
{
    /**
     * @param EmailConfig $emailConfig
     * @param TemplateOverrideRepositoryInterface $overrideRepository
     * @param TemplateAreaResolverInterface $areaResolver
     * @param TemplateFactory $templateFactory
     * @param Filesystem $filesystem
     * @param ReadFactory $readFactory
     * @param LoggerInterface $logger
     * @param PluginBypassFlagInterface $pluginBypassFlag
     */
    public function __construct(
        private readonly EmailConfig $emailConfig,
        private readonly TemplateOverrideRepositoryInterface $overrideRepository,
        private readonly TemplateAreaResolverInterface $areaResolver,
        private readonly TemplateFactory $templateFactory,
        private readonly Filesystem $filesystem,
        private readonly ReadFactory $readFactory,
        private readonly LoggerInterface $logger,
        private readonly PluginBypassFlagInterface $pluginBypassFlag
    ) {
    }

    /**
     * @inheritDoc
     */
    public function loadTemplateList(int $storeId = 0): array
    {
        $grouped = [];

        try {
            $templates = $this->emailConfig->getAvailableTemplates();

            foreach ($templates as $template) {
                $templateId = $template['value'];
                $label = $template['label'];
                $group = $template['group'] ?? $this->deriveGroupFromId($templateId);

                $grouped[$group][] = [
                    'id' => $templateId,
                    'label' => (string)$label,
                    'module' => $this->extractModuleName($templateId),
                    'area' => $this->areaResolver->resolve($templateId),
                    'overrides' => $this->getOverridesForTemplate($templateId, $storeId),
                ];
            }

            ksort($grouped);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load available templates: ' . $e->getMessage());
        }

        return $grouped;
    }

    /**
     * @inheritDoc
     */
    public function loadTemplate(
        string $identifier,
        int $storeId = 0,
        ?int $overrideEntityId = null,
        bool $defaultOnly = false
    ): array {
        $area = $this->areaResolver->resolve($identifier);
        $defaultData = $this->loadDefaultTemplate($identifier);
        $drafts = $this->overrideRepository->getDrafts($identifier, $storeId);
        $published = $this->overrideRepository->getPublished($identifier, $storeId);

        if ($defaultOnly) {
            return [
                'identifier' => $identifier,
                'label' => $this->getTemplateLabel($identifier),
                'module' => $this->extractModuleName($identifier),
                'area' => $area,
                'entity_id' => null,
                'default_content' => $defaultData['content'],
                'default_subject' => $defaultData['subject'],
                'default_styles' => $defaultData['styles'],
                'content' => $defaultData['content'],
                'subject' => $defaultData['subject'],
                'custom_css' => '',
                'tailwind_css' => '',
                'variables' => $defaultData['variables'],
                'type' => $defaultData['type'],
                'is_override' => false,
                'status' => '',
                'has_published' => $published !== null,
                'has_draft' => !empty($drafts),
                'published' => $published !== null ? $this->buildOverrideData($published) : null,
                'draft' => null,
                'drafts' => array_map([$this, 'buildOverrideData'], $drafts),
                'active_from' => '',
                'active_to' => '',
            ];
        }

        $activeOverride = null;

        if ($overrideEntityId !== null) {
            try {
                $activeOverride = $this->overrideRepository->getById($overrideEntityId);
            } catch (\Exception $e) {
                $this->logger->error('Failed to load override by ID: ' . $e->getMessage());
            }
        }

        if ($activeOverride === null && !empty($drafts)) {
            $activeOverride = reset($drafts);
        }

        if ($activeOverride !== null
            && $activeOverride->getStatus() === TemplateOverrideInterface::STATUS_PUBLISHED
        ) {
            $published = $activeOverride;
        }

        if ($activeOverride === null) {
            $activeOverride = $published;
        }

        $isActiveDraft = $activeOverride !== null
            && $activeOverride->getStatus() === TemplateOverrideInterface::STATUS_DRAFT;
        $isActivePublished = $activeOverride !== null
            && $activeOverride->getStatus() === TemplateOverrideInterface::STATUS_PUBLISHED;

        $scheduleSource = $isActivePublished ? $activeOverride : $published;

        return [
            'identifier' => $identifier,
            'label' => $this->getTemplateLabel($identifier),
            'module' => $this->extractModuleName($identifier),
            'area' => $area,
            'entity_id' => $activeOverride !== null ? $activeOverride->getEntityId() : null,
            'default_content' => $defaultData['content'],
            'default_subject' => $defaultData['subject'],
            'default_styles' => $defaultData['styles'],
            'content' => $activeOverride !== null && $activeOverride->getTemplateContent() !== null
                ? $activeOverride->getTemplateContent()
                : $defaultData['content'],
            'subject' => $activeOverride !== null && $activeOverride->getTemplateSubject() !== null
                ? $activeOverride->getTemplateSubject()
                : $defaultData['subject'],
            'custom_css' => $activeOverride !== null ? ($activeOverride->getCustomCss() ?? '') : '',
            'tailwind_css' => $activeOverride !== null ? ($activeOverride->getTailwindCss() ?? '') : '',
            'variables' => $defaultData['variables'],
            'type' => $defaultData['type'],
            'is_override' => $activeOverride !== null,
            'status' => $activeOverride !== null ? $activeOverride->getStatus() : '',
            'has_published' => $published !== null,
            'has_draft' => !empty($drafts),
            'published' => $published !== null ? $this->buildOverrideData($published) : null,
            'draft' => $isActiveDraft ? $this->buildOverrideData($activeOverride) : null,
            'drafts' => array_map([$this, 'buildOverrideData'], $drafts),
            'active_from' => $scheduleSource !== null ? ($scheduleSource->getActiveFrom() ?? '') : '',
            'active_to' => $scheduleSource !== null ? ($scheduleSource->getActiveTo() ?? '') : '',
        ];
    }

    /**
     * Load the default template file content and extract metadata
     *
     * @param string $identifier
     * @return array{content: string, subject: string, styles: string, variables: string, type: int}
     */
    private function loadDefaultTemplate(string $identifier): array
    {
        $result = [
            'content' => '',
            'subject' => '',
            'styles' => '',
            'variables' => '',
            'type' => 2,
        ];

        try {
            $parts = $this->emailConfig->parseTemplateIdParts($identifier);
            $baseTemplateId = $parts['templateId'];
            $theme = $parts['theme'] ?? null;

            $template = $this->templateFactory->create();
            $template->setForcedArea($baseTemplateId);

            if ($theme !== null) {
                $template->setForcedTheme($baseTemplateId, $theme);
            }

            $this->pluginBypassFlag->enable();

            try {
                $template->loadDefault($baseTemplateId);
            } finally {
                $this->pluginBypassFlag->disable();
            }

            $result['content'] = $template->getTemplateText() ?? '';
            $result['subject'] = $template->getTemplateSubject() ?? '';
            $result['styles'] = $template->getTemplateStyles() ?? '';
            $result['variables'] = $template->getData('orig_template_variables') ?? '';
            $result['type'] = (int)$template->getTemplateType();
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to load default template "' . $identifier . '": ' . $e->getMessage()
            );
        }

        return $result;
    }

    /**
     * Get the human-readable label for a template
     *
     * @param string $identifier
     * @return string
     */
    private function getTemplateLabel(string $identifier): string
    {
        try {
            $templates = $this->emailConfig->getAvailableTemplates();
            foreach ($templates as $template) {
                if ($template['value'] === $identifier) {
                    return (string)$template['label'];
                }
            }
        } catch (\Exception) {
            // Silently fall through
        }

        return $identifier;
    }

    /**
     * Extract the module name from a template identifier
     *
     * @param string $templateId
     * @return string
     */
    private function extractModuleName(string $templateId): string
    {
        try {
            $parts = $this->emailConfig->parseTemplateIdParts($templateId);
            $baseId = $parts['templateId'];
            $filePath = $this->emailConfig->getTemplateFilename($baseId);
            if (preg_match('#/([A-Z][a-z]+_[A-Z]\w+)/#', $filePath, $matches)) {
                return $matches[1];
            }
        } catch (\Exception) {
            // Silently fall through
        }

        $idParts = explode('_', $templateId);
        if (count($idParts) >= 2) {
            return ucfirst($idParts[0]) . '_' . ucfirst($idParts[1]);
        }

        return 'Unknown';
    }

    /**
     * Derive a group name from the template identifier
     *
     * @param string $templateId
     * @return string
     */
    private function deriveGroupFromId(string $templateId): string
    {
        $module = $this->extractModuleName($templateId);
        $parts = explode('_', $module);

        return $parts[1] ?? $parts[0] ?? 'Other';
    }

    /**
     * Get all overrides (draft, published, scheduled) for a template as sidebar children
     *
     * @param string $templateId
     * @param int $storeId
     * @return array<int, array{entity_id: int, label: string, status: string, scheduled_at: string|null, last_edited_by: string|null, updated_at: string|null}>
     */
    private function getOverridesForTemplate(string $templateId, int $storeId): array
    {
        $overrides = [];
        $seenIds = [];

        try {
            $publishedList = $this->overrideRepository->getPublishedList($templateId, $storeId);

            foreach ($publishedList as $published) {
                $overrides[] = $this->buildOverrideSummary($published);
                $seenIds[$published->getEntityId()] = true;
            }

            $scheduledList = $this->overrideRepository->getScheduledOverrides($templateId, $storeId);

            foreach ($scheduledList as $scheduled) {
                if (!isset($seenIds[$scheduled->getEntityId()])) {
                    $overrides[] = $this->buildOverrideSummary($scheduled);
                    $seenIds[$scheduled->getEntityId()] = true;
                }
            }

            $drafts = $this->overrideRepository->getDrafts($templateId, $storeId);

            foreach ($drafts as $draft) {
                if (!isset($seenIds[$draft->getEntityId()])) {
                    $overrides[] = $this->buildOverrideSummary($draft);
                }
            }
        } catch (\Exception) {
            // Silently fall through
        }

        return $overrides;
    }

    /**
     * Build a short summary of an override for the sidebar tree
     *
     * @param TemplateOverrideInterface $override
     * @return array{entity_id: int, label: string, draft_name: string|null, status: string, scheduled_at: string|null, active_from: string|null, active_to: string|null, last_edited_by: string|null, updated_at: string|null}
     */
    private function buildOverrideSummary(TemplateOverrideInterface $override): array
    {
        $draftName = $override->getDraftName();
        $comment = $override->getVersionComment();
        $label = $comment !== null && $comment !== ''
            ? $comment
            : ($draftName !== null && $draftName !== ''
                ? $draftName
                : 'Untitled');

        return [
            'entity_id' => $override->getEntityId(),
            'label' => $label,
            'version_comment' => $comment,
            'draft_name' => $draftName,
            'status' => $override->getStatus(),
            'scheduled_at' => $override->getScheduledAt(),
            'active_from' => $override->getActiveFrom(),
            'active_to' => $override->getActiveTo(),
            'created_by_username' => $override->getCreatedByUsername(),
            'last_edited_by' => $override->getLastEditedByUsername(),
            'created_at' => $override->getCreatedAt(),
            'updated_at' => $override->getUpdatedAt(),
            'is_active' => $override->getIsActive(),
        ];
    }

    /**
     * Build a structured data array from a template override entity
     *
     * @param TemplateOverrideInterface $override
     * @return array<string, mixed>
     */
    private function buildOverrideData(TemplateOverrideInterface $override): array
    {
        return [
            'entity_id' => $override->getEntityId(),
            'template_identifier' => $override->getTemplateIdentifier(),
            'template_content' => $override->getTemplateContent(),
            'template_subject' => $override->getTemplateSubject(),
            'custom_css' => $override->getCustomCss(),
            'tailwind_css' => $override->getTailwindCss(),
            'theme_id' => $override->getThemeId(),
            'store_id' => $override->getStoreId(),
            'status' => $override->getStatus(),
            'draft_name' => $override->getDraftName(),
            'active_from' => $override->getActiveFrom(),
            'active_to' => $override->getActiveTo(),
            'created_at' => $override->getCreatedAt(),
            'updated_at' => $override->getUpdatedAt(),
            'created_by_username' => $override->getCreatedByUsername(),
            'last_edited_by' => $override->getLastEditedByUsername(),
            'is_active' => $override->getIsActive(),
        ];
    }

}
