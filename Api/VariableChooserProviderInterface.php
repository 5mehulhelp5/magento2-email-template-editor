<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface VariableChooserProviderInterface
{
    /**
     * Get available template variable groups for the variable chooser panel
     *
     * @param string $templateId Template identifier
     * @param int $storeId Store ID for store-specific variables
     * @return array<string, array<int, array{label: string, value: string}>> Grouped variable definitions
     */
    public function getVariableGroups(string $templateId, int $storeId = 0): array;
}
