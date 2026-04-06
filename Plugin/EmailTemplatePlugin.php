<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Plugin;

use Hryvinskyi\EmailTemplateEditor\Api\ConfigInterface;
use Hryvinskyi\EmailTemplateEditor\Api\CssInlinerInterface;
use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterface;
use Hryvinskyi\EmailTemplateEditor\Api\PluginBypassFlagInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateOverrideRepositoryInterface;
use Hryvinskyi\EmailTemplateEditor\Api\ThemeRepositoryInterface;
use Hryvinskyi\EmailTemplateEditor\Api\UtilityCssGeneratorInterface;
use Magento\Email\Model\AbstractTemplate;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class EmailTemplatePlugin
{
    /**
     * @var array<string, string>
     */
    private array $pendingCssMap = [];

    /**
     * @param ConfigInterface $config
     * @param TemplateOverrideRepositoryInterface $overrideRepository
     * @param ThemeRepositoryInterface $themeRepository
     * @param UtilityCssGeneratorInterface $cssGenerator
     * @param CssInlinerInterface $cssInliner
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param PluginBypassFlagInterface $pluginBypassFlag
     */
    public function __construct(
        private readonly ConfigInterface $config,
        private readonly TemplateOverrideRepositoryInterface $overrideRepository,
        private readonly ThemeRepositoryInterface $themeRepository,
        private readonly UtilityCssGeneratorInterface $cssGenerator,
        private readonly CssInlinerInterface $cssInliner,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger,
        private readonly PluginBypassFlagInterface $pluginBypassFlag
    ) {
    }

    /**
     * After loading default template, apply the best matching published override
     *
     * @param AbstractTemplate $subject
     * @param AbstractTemplate $result
     * @param string $templateId
     * @return AbstractTemplate
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoadDefault(
        AbstractTemplate $subject,
        AbstractTemplate $result,
        string $templateId
    ): AbstractTemplate {
        if ($this->pluginBypassFlag->isBypassed()) {
            return $result;
        }

        try {
            $storeId = (int)$this->storeManager->getStore()->getId();

            if (!$this->config->isEnabled($storeId)) {
                return $result;
            }

            $override = $this->loadPublishedOverride($templateId, $storeId);

            if ($override === null || !$override->getIsActive()) {
                return $result;
            }

            $result->setTemplateText($override->getTemplateContent());

            if ($override->getTemplateSubject()) {
                $result->setTemplateSubject($override->getTemplateSubject());
            }

            $combinedCss = $this->buildCombinedCss($override, $storeId);
            if ($combinedCss !== '') {
                $this->pendingCssMap[spl_object_id($result)] = $combinedCss;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'EmailTemplateEditor plugin error for template "' . $templateId . '": ' . $e->getMessage()
            );
        }

        return $result;
    }

    /**
     * After processing the template, inline the override CSS into the HTML
     *
     * @param AbstractTemplate $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetProcessedTemplate(AbstractTemplate $subject, string $result): string
    {
        $objectId = spl_object_id($subject);

        if (!isset($this->pendingCssMap[$objectId])) {
            return $result;
        }

        $css = $this->pendingCssMap[$objectId];
        unset($this->pendingCssMap[$objectId]);

        try {
            return $this->cssInliner->inline($result, null, null, $css);
        } catch (\Exception $e) {
            $this->logger->error('Failed to inline CSS for email: ' . $e->getMessage());

            return $result;
        }
    }

    /**
     * Load the best matching published override with store fallback
     *
     * Priority: scheduled override (active_from/active_to covers now) first,
     * then immediate override (no date range) as fallback.
     *
     * @param string $templateId
     * @param int $storeId
     * @return TemplateOverrideInterface|null
     */
    private function loadPublishedOverride(string $templateId, int $storeId): ?TemplateOverrideInterface
    {
        $storeIds = [$storeId];
        if ($storeId !== 0) {
            $storeIds[] = 0;
        }

        foreach ($storeIds as $sid) {
            $override = $this->overrideRepository->getActiveScheduledPublished($templateId, $sid);
            if ($override !== null) {
                return $override;
            }
        }

        foreach ($storeIds as $sid) {
            $override = $this->overrideRepository->getImmediatePublished($templateId, $sid);
            if ($override !== null) {
                return $override;
            }
        }

        return null;
    }

    /**
     * Build combined CSS from theme, tailwind, and custom CSS
     *
     * @param TemplateOverrideInterface $override
     * @param int $storeId
     * @return string
     */
    private function buildCombinedCss(TemplateOverrideInterface $override, int $storeId): string
    {
        $parts = [];

        $themeId = $override->getThemeId();
        try {
            if ($themeId) {
                $theme = $this->themeRepository->getById($themeId);
                $themeCss = $this->cssGenerator->generate($theme->getThemeJson());
            } else {
                $defaultTheme = $this->themeRepository->getDefaultTheme($storeId);
                $themeCss = $defaultTheme
                    ? $this->cssGenerator->generate($defaultTheme->getThemeJson())
                    : '';
            }
            if ($themeCss !== '') {
                $parts[] = $themeCss;
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to load theme CSS: ' . $e->getMessage());
        }

        $tailwindCss = $override->getTailwindCss();
        if ($tailwindCss !== null && $tailwindCss !== '') {
            $parts[] = $tailwindCss;
        }

        $customCss = $override->getCustomCss();
        if ($customCss !== null && $customCss !== '') {
            $parts[] = $customCss;
        }

        return implode("\n", $parts);
    }
}
