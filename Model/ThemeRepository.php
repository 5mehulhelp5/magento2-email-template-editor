<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\Data\ThemeInterface;
use Hryvinskyi\EmailTemplateEditor\Api\ThemeRepositoryInterface;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\Theme as ThemeResource;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\Theme\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ThemeRepository implements ThemeRepositoryInterface
{
    /**
     * @param ThemeFactory $themeFactory
     * @param ThemeResource $resource
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        private readonly ThemeFactory $themeFactory,
        private readonly ThemeResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory,
        private readonly CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getById(int $themeId): ThemeInterface
    {
        $theme = $this->themeFactory->create();
        $this->resource->load($theme, $themeId);

        if (!$theme->getThemeId()) {
            throw new NoSuchEntityException(__('Theme with ID "%1" does not exist.', $themeId));
        }

        return $theme;
    }

    /**
     * @inheritDoc
     */
    public function save(ThemeInterface $theme): ThemeInterface
    {
        try {
            $this->resource->save($theme);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save theme: %1', $e->getMessage()), $e);
        }

        return $theme;
    }

    /**
     * @inheritDoc
     */
    public function delete(ThemeInterface $theme): bool
    {
        try {
            $this->resource->delete($theme);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete theme: %1', $e->getMessage()), $e);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultTheme(int $storeId): ?ThemeInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('is_default', 1);
        $collection->addFieldToFilter('store_id', ['in' => [0, $storeId]]);
        $collection->setOrder('store_id', 'DESC');
        $collection->setPageSize(1);

        $theme = $collection->getFirstItem();

        return $theme->getThemeId() ? $theme : null;
    }
}
