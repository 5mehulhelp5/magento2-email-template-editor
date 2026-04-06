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
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class LastCustomerProvider implements SampleDataProviderInterface
{
    /**
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param TemplateSampleDataMapperInterface $templateMapper
     * @param MockVariableBuilderPoolInterface $builderPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CustomerCollectionFactory $customerCollectionFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly TemplateSampleDataMapperInterface $templateMapper,
        private readonly MockVariableBuilderPoolInterface $builderPool,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Last Customer';
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'last_customer';
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

        try {
            $collection = $this->customerCollectionFactory->create();

            if ($storeId) {
                $collection->addFieldToFilter('store_id', $storeId);
            }

            $collection->setOrder('entity_id', 'DESC');
            $collection->setPageSize(1);
            $customerModel = $collection->getFirstItem();

            if (!$customerModel || !$customerModel->getId()) {
                return $this->builderPool->getBuilder($category)->build($templateIdentifier, $storeId);
            }

            $customer = $this->customerRepository->getById((int)$customerModel->getId());
            $store = $this->storeManager->getStore($customer->getStoreId());

            $variables = [
                'customer' => new DataObject([
                    'name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                    'firstname' => $customer->getFirstname(),
                    'lastname' => $customer->getLastname(),
                    'email' => $customer->getEmail(),
                    'id' => $customer->getId(),
                ]),
                'customer_name' => $customer->getFirstname() . ' ' . $customer->getLastname(),
                'customer_email' => $customer->getEmail(),
                'store' => $store,
                'store_name' => $store->getName(),
                'store_url' => $store->getBaseUrl(),
                'logo_url' => '',
                'logo_alt' => $store->getName(),
            ];

            // Merge template-specific extras from mock data (but keep real customer data)
            $mockVars = $this->builderPool->getBuilder($category)->build($templateIdentifier, $storeId);
            // Only merge keys that aren't already set from real data
            foreach ($mockVars as $key => $value) {
                if (!isset($variables[$key])) {
                    $variables[$key] = $value;
                }
            }

            return $variables;
        } catch (\Exception $e) {
            $this->logger->error('Failed to load last customer sample data: ' . $e->getMessage());
            return $this->builderPool->getBuilder($category)->build($templateIdentifier, $storeId);
        }
    }
}
