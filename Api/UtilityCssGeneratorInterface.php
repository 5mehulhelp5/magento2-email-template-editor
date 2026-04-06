<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface UtilityCssGeneratorInterface
{
    /**
     * Generate utility CSS classes from a theme JSON configuration string
     *
     * @param string $themeJson JSON string containing theme token definitions
     * @return string Generated CSS with utility classes
     */
    public function generate(string $themeJson): string;
}
