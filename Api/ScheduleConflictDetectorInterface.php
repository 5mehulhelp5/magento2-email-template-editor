<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface ScheduleConflictDetectorInterface
{
    /**
     * Detect overlapping scheduled overrides for the given template and store
     *
     * @param string $templateIdentifier
     * @param int $storeId
     * @param string|null $activeFrom
     * @param string|null $activeTo
     * @param int|null $excludeEntityId
     * @return array<int, array{entity_id: int, draft_name: string|null, active_from: string|null, active_to: string|null}>
     */
    public function detect(
        string $templateIdentifier,
        int $storeId,
        ?string $activeFrom,
        ?string $activeTo,
        ?int $excludeEntityId = null
    ): array;
}
