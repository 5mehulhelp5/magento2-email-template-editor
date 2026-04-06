<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface CssInlinerInterface
{
    /**
     * Inline CSS styles into HTML elements for email client compatibility
     *
     * @param string $html HTML content to process
     * @param string|null $customCss User-written custom CSS to inline
     * @param string|null $tailwindCss Auto-generated Tailwind CSS to inline
     * @param string|null $themeCss Theme-generated CSS to inline
     * @return string HTML with inlined CSS styles
     */
    public function inline(
        string $html,
        ?string $customCss = null,
        ?string $tailwindCss = null,
        ?string $themeCss = null
    ): string;
}
