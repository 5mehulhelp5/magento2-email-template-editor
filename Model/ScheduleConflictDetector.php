<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterface;
use Hryvinskyi\EmailTemplateEditor\Api\ScheduleConflictDetectorInterface;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\TemplateOverride\CollectionFactory;

class ScheduleConflictDetector implements ScheduleConflictDetectorInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function detect(
        string $templateIdentifier,
        int $storeId,
        ?string $activeFrom,
        ?string $activeTo,
        ?int $excludeEntityId = null
    ): array {
        if ($activeFrom === null && $activeTo === null) {
            return [];
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter(TemplateOverrideInterface::TEMPLATE_IDENTIFIER, $templateIdentifier);
        $collection->addFieldToFilter(TemplateOverrideInterface::STORE_ID, $storeId);
        $collection->addFieldToFilter(TemplateOverrideInterface::STATUS, [
            'in' => [
                TemplateOverrideInterface::STATUS_PUBLISHED,
                TemplateOverrideInterface::STATUS_SCHEDULED,
            ]
        ]);

        if ($excludeEntityId !== null) {
            $collection->addFieldToFilter(TemplateOverrideInterface::ENTITY_ID, ['neq' => $excludeEntityId]);
        }

        $conflicts = [];

        /** @var TemplateOverrideInterface $override */
        foreach ($collection as $override) {
            $existingFrom = $override->getActiveFrom();
            $existingTo = $override->getActiveTo();

            if (empty($existingFrom) && empty($existingTo)) {
                continue;
            }

            if ($this->isOverlapping($activeFrom, $activeTo, $existingFrom, $existingTo)) {
                $conflicts[] = [
                    'entity_id' => $override->getEntityId(),
                    'draft_name' => $override->getDraftName(),
                    'active_from' => $existingFrom,
                    'active_to' => $existingTo,
                ];
            }
        }

        return $conflicts;
    }

    /**
     * Check if two date ranges overlap (null = open-ended)
     *
     * @param string|null $fromA
     * @param string|null $toA
     * @param string|null $fromB
     * @param string|null $toB
     * @return bool
     */
    private function isOverlapping(?string $fromA, ?string $toA, ?string $fromB, ?string $toB): bool
    {
        $startA = $fromA !== null && $fromA !== '' ? strtotime($fromA) : 0;
        $endA = $toA !== null && $toA !== '' ? strtotime($toA) : PHP_INT_MAX;
        $startB = $fromB !== null && $fromB !== '' ? strtotime($fromB) : 0;
        $endB = $toB !== null && $toB !== '' ? strtotime($toB) : PHP_INT_MAX;

        return $startA < $endB && $startB < $endA;
    }
}
