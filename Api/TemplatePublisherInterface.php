<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface TemplatePublisherInterface
{
    /**
     * Publish a template override immediately, creating a version snapshot
     *
     * @param int $overrideId
     * @param string|null $versionComment
     * @return int Published entity ID
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function publish(int $overrideId, ?string $versionComment = null): int;

    /**
     * Schedule a template override for future publication
     *
     * @param int $overrideId
     * @param string $scheduledAt DateTime string for when the override should be published
     * @param string|null $versionComment
     * @param string|null $activeFrom Schedule start datetime
     * @param string|null $activeTo Schedule end datetime
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function schedulePublish(
        int $overrideId,
        string $scheduledAt,
        ?string $versionComment = null,
        ?string $activeFrom = null,
        ?string $activeTo = null
    ): void;

    /**
     * Update the schedule (active_from/active_to) of a published override in place
     *
     * @param int $overrideId
     * @param string|null $activeFrom
     * @param string|null $activeTo
     * @return void
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function updateSchedule(int $overrideId, ?string $activeFrom, ?string $activeTo): void;
}
