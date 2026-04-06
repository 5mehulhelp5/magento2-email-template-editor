<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\SampleData\MockVariableBuilder;

use Hryvinskyi\EmailTemplateEditor\Api\MockVariableBuilderInterface;
use Magento\Framework\DataObject;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class AdminMockBuilder implements MockVariableBuilderInterface
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
    public function build(string $templateIdentifier, int $storeId): array
    {
        $store = $this->storeManager->getStore($storeId);

        if (str_starts_with($templateIdentifier, 'admin_emails')
            || str_starts_with($templateIdentifier, 'admin_adobe_ims_email')
        ) {
            return $this->getAdminUserVars($store);
        }

        if (str_starts_with($templateIdentifier, 'design_email_header')
            || str_starts_with($templateIdentifier, 'design_email_footer')
        ) {
            return $this->getHeaderFooterVars($store);
        }

        return $this->getAdminUserVars($store);
    }

    /**
     * @param StoreInterface $store
     * @return array<string, mixed>
     */
    private function getAdminUserVars(StoreInterface $store): array
    {
        return [
            'user' => new DataObject([
                'name' => 'Admin User',
                'firstname' => 'Admin',
                'lastname' => 'User',
                'email' => 'admin@example.com',
                'username' => 'admin',
            ]),
            'customer_name' => 'Admin User',
            'store' => $store,
        ];
    }

    /**
     * @param StoreInterface $store
     * @return array<string, mixed>
     */
    private function getHeaderFooterVars(StoreInterface $store): array
    {
        return [
            'logo_url' => '',
            'logo_alt' => $store->getName(),
            'logo_width' => 200,
            'logo_height' => 50,
            'store' => $store,
        ];
    }
}
