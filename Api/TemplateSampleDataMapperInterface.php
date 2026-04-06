<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface TemplateSampleDataMapperInterface
{
    /**
     * Map a template identifier to its data category (e.g., "order", "customer", "newsletter")
     *
     * @param string $templateIdentifier
     * @return string Category code
     */
    public function getCategory(string $templateIdentifier): string;
}
