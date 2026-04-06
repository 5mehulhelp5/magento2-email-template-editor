<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\SampleData;

use Hryvinskyi\EmailTemplateEditor\Api\SampleDataProviderInterface;
use Magento\Store\Model\StoreManagerInterface;

class CustomDataProvider implements SampleDataProviderInterface
{
    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Custom JSON';
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'custom';
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
        try {
            $store = $this->storeManager->getStore($storeId ?: null);
        } catch (\Exception) {
            $store = $this->storeManager->getDefaultStoreView();
        }

        return [
            'store' => $store,
            'store_name' => $store->getName(),
            'store_url' => $store->getBaseUrl(),
            'store_email' => 'support@example.com',
            'store_phone' => '+1 (555) 123-4567',
            'logo_url' => '',
            'logo_alt' => $store->getName(),
        ];
    }
}
