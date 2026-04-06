<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface TemplateOverrideRepositoryInterface
{
    /**
     * Get template override by ID
     *
     * @param int $entityId
     * @return TemplateOverrideInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $entityId): TemplateOverrideInterface;

    /**
     * Save a template override
     *
     * @param TemplateOverrideInterface $override
     * @return TemplateOverrideInterface
     * @throws CouldNotSaveException
     */
    public function save(TemplateOverrideInterface $override): TemplateOverrideInterface;

    /**
     * Delete a template override
     *
     * @param TemplateOverrideInterface $override
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(TemplateOverrideInterface $override): bool;

    /**
     * Get override by template identifier, store ID, and status
     *
     * @param string $identifier
     * @param int $storeId
     * @param string $status
     * @return TemplateOverrideInterface|null
     */
    public function getByIdentifier(string $identifier, int $storeId, string $status): ?TemplateOverrideInterface;

    /**
     * Get draft override for a template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @return TemplateOverrideInterface|null
     */
    public function getDraft(string $identifier, int $storeId): ?TemplateOverrideInterface;

    /**
     * Get published override for a template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @return TemplateOverrideInterface|null
     */
    public function getPublished(string $identifier, int $storeId): ?TemplateOverrideInterface;

    /**
     * Get scheduled override for a template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @return TemplateOverrideInterface|null
     */
    public function getScheduled(string $identifier, int $storeId): ?TemplateOverrideInterface;

    /**
     * Get all draft overrides for a template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @return TemplateOverrideInterface[]
     */
    public function getDrafts(string $identifier, int $storeId): array;

    /**
     * Get all scheduled overrides for a template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @return TemplateOverrideInterface[]
     */
    public function getScheduledOverrides(string $identifier, int $storeId): array;

    /**
     * Get all published overrides for a template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @return TemplateOverrideInterface[]
     */
    public function getPublishedList(string $identifier, int $storeId): array;

    /**
     * Get the immediate (no date range) published override for a template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @return TemplateOverrideInterface|null
     */
    public function getImmediatePublished(string $identifier, int $storeId): ?TemplateOverrideInterface;

    /**
     * Get the published override whose active_from/active_to range covers the current time
     *
     * @param string $identifier
     * @param int $storeId
     * @return TemplateOverrideInterface|null
     */
    public function getActiveScheduledPublished(string $identifier, int $storeId): ?TemplateOverrideInterface;
}
