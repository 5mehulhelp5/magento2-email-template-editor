<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateVersionInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface TemplateVersionRepositoryInterface
{
    /**
     * Get template version by ID
     *
     * @param int $versionId
     * @return TemplateVersionInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $versionId): TemplateVersionInterface;

    /**
     * Save a template version
     *
     * @param TemplateVersionInterface $version
     * @return TemplateVersionInterface
     * @throws CouldNotSaveException
     */
    public function save(TemplateVersionInterface $version): TemplateVersionInterface;

    /**
     * Delete a template version
     *
     * @param TemplateVersionInterface $version
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(TemplateVersionInterface $version): bool;

    /**
     * Get version list for a template identifier and store ID, ordered by version number descending
     *
     * @param string $identifier
     * @param int $storeId
     * @param int $limit Maximum number of versions to return
     * @return array<TemplateVersionInterface>
     */
    public function getVersionList(string $identifier, int $storeId, int $limit = 50): array;

    /**
     * Get a specific version by its version number for a given template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @param int $versionNumber
     * @return TemplateVersionInterface|null
     */
    public function getByVersionNumber(string $identifier, int $storeId, int $versionNumber): ?TemplateVersionInterface;

    /**
     * Get the next version number for a template identifier and store ID
     *
     * @param string $identifier
     * @param int $storeId
     * @return int
     */
    public function getNextVersionNumber(string $identifier, int $storeId): int;
}
