<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateOverrideRepositoryInterface;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\TemplateOverride as TemplateOverrideResource;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\TemplateOverride\CollectionFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class TemplateOverrideRepository implements TemplateOverrideRepositoryInterface
{
    /**
     * @param TemplateOverrideFactory $overrideFactory
     * @param TemplateOverrideResource $resource
     * @param CollectionFactory $collectionFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private readonly TemplateOverrideFactory $overrideFactory,
        private readonly TemplateOverrideResource $resource,
        private readonly CollectionFactory $collectionFactory,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getById(int $entityId): TemplateOverrideInterface
    {
        $override = $this->overrideFactory->create();
        $this->resource->load($override, $entityId);

        if (!$override->getEntityId()) {
            throw new NoSuchEntityException(__('Template override with ID "%1" does not exist.', $entityId));
        }

        return $override;
    }

    /**
     * @inheritDoc
     */
    public function save(TemplateOverrideInterface $override): TemplateOverrideInterface
    {
        try {
            $this->resource->save($override);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save template override: %1', $e->getMessage()), $e);
        }

        return $override;
    }

    /**
     * @inheritDoc
     */
    public function delete(TemplateOverrideInterface $override): bool
    {
        try {
            $this->resource->delete($override);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete template override: %1', $e->getMessage()), $e);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getByIdentifier(string $identifier, int $storeId, string $status): ?TemplateOverrideInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->addFieldToFilter('status', $status);
        $collection->addFieldToFilter('is_active', 1);
        $collection->setOrder('entity_id', 'DESC');
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item->getEntityId() ? $item : null;
    }

    /**
     * @inheritDoc
     */
    public function getDraft(string $identifier, int $storeId): ?TemplateOverrideInterface
    {
        return $this->getByIdentifier($identifier, $storeId, TemplateOverrideInterface::STATUS_DRAFT);
    }

    /**
     * @inheritDoc
     */
    public function getPublished(string $identifier, int $storeId): ?TemplateOverrideInterface
    {
        return $this->getByIdentifier($identifier, $storeId, TemplateOverrideInterface::STATUS_PUBLISHED);
    }

    /**
     * @inheritDoc
     */
    public function getScheduled(string $identifier, int $storeId): ?TemplateOverrideInterface
    {
        return $this->getByIdentifier($identifier, $storeId, TemplateOverrideInterface::STATUS_SCHEDULED);
    }

    /**
     * @inheritDoc
     */
    public function getDrafts(string $identifier, int $storeId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->addFieldToFilter('status', TemplateOverrideInterface::STATUS_DRAFT);
//        $collection->setOrder('updated_at', 'DESC');

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getScheduledOverrides(string $identifier, int $storeId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->addFieldToFilter('status', TemplateOverrideInterface::STATUS_SCHEDULED);
        $collection->setOrder('scheduled_at', 'ASC');

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getPublishedList(string $identifier, int $storeId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->addFieldToFilter('status', TemplateOverrideInterface::STATUS_PUBLISHED);
//        $collection->setOrder('updated_at', 'DESC');

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getImmediatePublished(string $identifier, int $storeId): ?TemplateOverrideInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->addFieldToFilter('status', TemplateOverrideInterface::STATUS_PUBLISHED);
        $collection->addFieldToFilter('active_from', ['null' => true]);
        $collection->addFieldToFilter('active_to', ['null' => true]);
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item->getEntityId() ? $item : null;
    }

    /**
     * @inheritDoc
     */
    public function getActiveScheduledPublished(string $identifier, int $storeId): ?TemplateOverrideInterface
    {
        $now = $this->timezone->date()->format('Y-m-d H:i:s');

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('template_identifier', $identifier);
        $collection->addFieldToFilter('store_id', $storeId);
        $collection->addFieldToFilter('status', TemplateOverrideInterface::STATUS_PUBLISHED);
        $collection->addFieldToFilter('is_active', 1);
        $collection->addFieldToFilter('active_from', ['lteq' => $now]);
        $collection->addFieldToFilter('active_to', ['gteq' => $now]);
        $collection->setPageSize(1);

        $item = $collection->getFirstItem();

        return $item->getEntityId() ? $item : null;
    }
}
