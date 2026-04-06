<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface CssVariableResolverInterface
{
    /**
     * Resolve CSS custom properties (var() references) to their computed values
     *
     * Parses CSS for custom property definitions and replaces var() function calls
     * with the resolved values. This is necessary for email client compatibility
     * since most email clients do not support CSS custom properties.
     *
     * @param string $css CSS content containing custom property definitions and var() references
     * @return string CSS with var() references replaced by resolved values
     */
    public function resolve(string $css): string;
}
