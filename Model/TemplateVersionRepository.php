<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateVersionInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateVersionRepositoryInterface;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\TemplateVersion as TemplateVersionResource;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\TemplateVersion\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class TemplateVersionRepository implements TemplateVersionRepositoryInterface
{
    /**
     * @param TemplateVersionFactory $versionFactory
     * @param TemplateVersionResource $resource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly TemplateVersionFactory $versionFactory,
        private readonly TemplateVersionResource $resource,
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getById(int $versionId): TemplateVersionInterface
    {
        $version = $this->versionFactory->create();
        $this->resource->load($version, $versionId);

        if (!$version->getVersionId()) {
            throw new NoSuchEntityException(__('Template version with ID "%1" does not exist.', $versionId));
        }

        return $version;
    }

    /**
     * @inheritDoc
     */
    public function save(TemplateVersionInterface $version): TemplateVersionInterface
    {
        try {
            $this->resource->save($version);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save template version: %1', $e->getMessage()), $e);
        }

        return $version;
    }

    /**
     * @inheritDoc
     */
    public function delete(TemplateVersionInterface $version): bool
    {
        try {
            $this->resource->delete($version);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete template version: %1', $e->getMessage()), $e);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getVersionList(string $identifier, int $storeId, int $limit = 50): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->setOrder('version_number', 'DESC');
        $collection->setPageSize($limit);

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getByVersionNumber(string $identifier, int $storeId, int $versionNumber): ?TemplateVersionInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->addFieldToFilter('version_number', $versionNumber);
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item->getVersionId() ? $item : null;
    }

    /**
     * @inheritDoc
     */
    public function getNextVersionNumber(string $identifier, int $storeId): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->setOrder('version_number', 'DESC');
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();

        if ($item->getVersionId()) {
            return $item->getVersionNumber() + 1;
        }

        return 1;
    }
}
