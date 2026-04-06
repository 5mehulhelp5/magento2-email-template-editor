<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\CssInlinerInterface;
use Hryvinskyi\EmailTemplateEditor\Api\CssVariableResolverInterface;
use Pelago\Emogrifier\CssInliner as EmogrifierCssInliner;
use Psr\Log\LoggerInterface;

class CssInliner implements CssInlinerInterface
{
    /**
     * @param CssVariableResolverInterface $cssVariableResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CssVariableResolverInterface $cssVariableResolver,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function inline(
        string $html,
        ?string $customCss = null,
        ?string $tailwindCss = null,
        ?string $themeCss = null
    ): string {
        $cssParts = array_filter([$customCss, $tailwindCss, $themeCss], static function (?string $css): bool {
            return $css !== null && trim($css) !== '';
        });

        if (empty($cssParts)) {
            return $html;
        }

        $combinedCss = implode("\n", $cssParts);
        $combinedCss = $this->cssVariableResolver->resolve($combinedCss);

        try {
            return EmogrifierCssInliner::fromHtml($html)
                ->inlineCss($combinedCss)
                ->render();
        } catch (\Exception $e) {
            $this->logger->error('CSS inlining failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $html;
        }
    }
}
