<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\CssInlinerInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateAreaResolverInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateRendererInterface;
use Hryvinskyi\EmailTemplateEditor\Api\ThemeRepositoryInterface;
use Hryvinskyi\EmailTemplateEditor\Api\UtilityCssGeneratorInterface;
use Magento\Email\Model\TemplateFactory;
use Magento\Framework\App\State;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class TemplateRenderer implements TemplateRendererInterface
{
    /**
     * @param TemplateAreaResolverInterface $areaResolver
     * @param CssInlinerInterface $cssInliner
     * @param UtilityCssGeneratorInterface $utilityCssGenerator
     * @param ThemeRepositoryInterface $themeRepository
     * @param State $appState
     * @param Emulation $appEmulation
     * @param TemplateFactory $templateFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly TemplateAreaResolverInterface $areaResolver,
        private readonly CssInlinerInterface $cssInliner,
        private readonly UtilityCssGeneratorInterface $utilityCssGenerator,
        private readonly ThemeRepositoryInterface $themeRepository,
        private readonly State $appState,
        private readonly Emulation $appEmulation,
        private readonly TemplateFactory $templateFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function render(
        string $content,
        array $variables,
        int $storeId,
        ?string $customCss = null,
        ?string $tailwindCss = null,
        ?string $templateIdentifier = null,
        bool $isMockData = false
    ): string {
        $emulationStarted = false;

        try {
            $area = $templateIdentifier !== null
                ? $this->areaResolver->resolve($templateIdentifier)
                : $this->resolveAreaForContent($content);

            if ($storeId === 0) {
                $storeId = (int)$this->getDefaultFrontendStoreId();
            }

            if ($isMockData) {
                $content = $this->replaceLayoutDirectives($content);
            }

            $this->appEmulation->startEnvironmentEmulation($storeId, $area, true);
            $emulationStarted = true;

            $template = $this->templateFactory->create();
            $template->setTemplateType(\Magento\Email\Model\Template::TYPE_HTML);
            $template->setTemplateText($content);

            $themeCss = $this->resolveThemeCss($storeId);
            if ($themeCss !== null && trim($themeCss) !== '') {
                $template->setTemplateStyles($themeCss);
            }

            $processedContent = $this->appState->emulateAreaCode(
                $area,
                function () use ($template, $variables): string {
                    return $template->getProcessedTemplate($variables);
                }
            );

            $this->appEmulation->stopEnvironmentEmulation();
            $emulationStarted = false;

            $processedContent = $this->cssInliner->inline(
                $processedContent,
                $customCss,
                $tailwindCss,
                $themeCss
            );

            return $processedContent;
        } catch (\Exception $e) {
            $this->logger->error('Template rendering failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return '<div style="color:red;padding:20px;">Rendering Error: '
                . htmlspecialchars($e->getMessage()) . '</div>';
        } finally {
            if ($emulationStarted) {
                $this->appEmulation->stopEnvironmentEmulation();
            }
        }
    }

    /**
     * Resolve theme CSS for the given store by loading the default theme
     *
     * @param int $storeId
     * @return string|null
     */
    private function resolveThemeCss(int $storeId): ?string
    {
        try {
            $theme = $this->themeRepository->getDefaultTheme($storeId);
            if ($theme !== null && $theme->getThemeJson() !== null) {
                return $this->utilityCssGenerator->generate($theme->getThemeJson());
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to resolve theme CSS: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Replace {{layout}} directives with placeholder HTML since they require real DB entities
     *
     * @param string $content
     * @return string
     */
    private function replaceLayoutDirectives(string $content): string
    {
        return (string)preg_replace_callback(
            '/\{\{layout\s+([^}]*)\}\}/i',
            function (array $matches): string {
                $handle = '';

                if (preg_match('/handle\s*=\s*"([^"]+)"/', $matches[1], $handleMatch)) {
                    $handle = $handleMatch[1];
                }

                $label = $handle !== '' ? htmlspecialchars($handle) : 'layout block';

                return '<table style="width:100%;border:1px dashed #e3e3e3;border-radius:4px;margin:12px 0;">'
                    . '<tr><td style="padding:20px;text-align:center;color:#999;font-size:13px;font-style:italic;">'
                    . '[ ' . $label . ' — requires real data to render ]'
                    . '</td></tr></table>';
            },
            $content
        );
    }

    /**
     * Resolve the application area based on template content heuristics
     *
     * @param string $content
     * @return string
     */
    private function resolveAreaForContent(string $content): string
    {
        if (str_contains($content, 'adminhtml') || str_contains($content, 'backend')) {
            return 'adminhtml';
        }

        return 'frontend';
    }

    /**
     * Get the first available frontend store ID
     *
     * @return int
     */
    private function getDefaultFrontendStoreId(): int
    {
        try {
            $stores = $this->storeManager->getStores(false);

            foreach ($stores as $store) {
                if ($store->isActive()) {
                    return (int)$store->getId();
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to resolve default frontend store: ' . $e->getMessage());
        }

        return 1;
    }
}
