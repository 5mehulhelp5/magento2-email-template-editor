<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

use Hryvinskyi\EmailTemplateEditor\Api\Data\ThemeInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

interface ThemeRepositoryInterface
{
    /**
     * Get theme by ID
     *
     * @param int $themeId
     * @return ThemeInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $themeId): ThemeInterface;

    /**
     * Save a theme
     *
     * @param ThemeInterface $theme
     * @return ThemeInterface
     * @throws CouldNotSaveException
     */
    public function save(ThemeInterface $theme): ThemeInterface;

    /**
     * Delete a theme
     *
     * @param ThemeInterface $theme
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ThemeInterface $theme): bool;

    /**
     * Retrieve themes matching the specified search criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Get the default theme for a given store
     *
     * @param int $storeId
     * @return ThemeInterface|null
     */
    public function getDefaultTheme(int $storeId): ?ThemeInterface;
}
