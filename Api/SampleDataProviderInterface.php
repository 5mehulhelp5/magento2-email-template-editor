<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface SampleDataProviderInterface
{
    /**
     * Get unique code identifying this provider
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Get human-readable label for this provider
     *
     * @return string
     */
    public function getLabel(): string;

    /**
     * Get template variables populated with sample data from this provider
     *
     * @param string $templateIdentifier Template identifier for context-specific data
     * @param int $storeId Store ID for store-scoped data
     * @param string|null $entityId Optional entity ID for entity-specific data (e.g., order ID)
     * @return array<string, mixed>
     */
    public function getVariables(string $templateIdentifier, int $storeId, ?string $entityId = null): array;

    /**
     * Whether this provider supports searching for specific entities (e.g., orders, customers)
     *
     * @return bool
     */
    public function supportsEntitySearch(): bool;

    /**
     * Search for entities matching the query for entity selection
     *
     * @param string $query Search query string
     * @param int $storeId Store ID scope
     * @param int $limit Maximum number of results to return
     * @return array<int, array{id: string, label: string}>
     */
    public function searchEntities(string $query, int $storeId, int $limit = 10): array;
}
