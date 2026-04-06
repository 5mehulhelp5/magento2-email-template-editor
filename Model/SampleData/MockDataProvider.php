<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\SampleData;

use Hryvinskyi\EmailTemplateEditor\Api\MockVariableBuilderPoolInterface;
use Hryvinskyi\EmailTemplateEditor\Api\SampleDataProviderInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateSampleDataMapperInterface;

class MockDataProvider implements SampleDataProviderInterface
{
    /**
     * @param TemplateSampleDataMapperInterface $templateMapper
     * @param MockVariableBuilderPoolInterface $builderPool
     */
    public function __construct(
        private readonly TemplateSampleDataMapperInterface $templateMapper,
        private readonly MockVariableBuilderPoolInterface $builderPool
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Mock Data';
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'mock';
    }

    /**
     * @inheritDoc
     */
    public function supportsEntitySearch(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function searchEntities(string $query, int $storeId, int $limit = 10): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getVariables(string $templateIdentifier, int $storeId, ?string $entityId = null): array
    {
        $category = $this->templateMapper->getCategory($templateIdentifier);
        return $this->builderPool->getBuilder($category)->build($templateIdentifier, $storeId);
    }
}
