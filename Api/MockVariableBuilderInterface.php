<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface MockVariableBuilderInterface
{
    /**
     * Build mock template variables for a given template identifier and store
     *
     * @param string $templateIdentifier Template identifier for context-specific mock data
     * @param int $storeId Store ID for store-scoped mock data
     * @return array<string, mixed>
     */
    public function build(string $templateIdentifier, int $storeId): array;
}
