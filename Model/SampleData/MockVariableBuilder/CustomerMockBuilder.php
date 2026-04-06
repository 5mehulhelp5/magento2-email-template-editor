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

class CustomerMockBuilder implements MockVariableBuilderInterface
{
    /**
     * @inheritDoc
     */
    public function build(string $templateIdentifier, int $storeId): array
    {
        if (str_starts_with($templateIdentifier, 'customer_create_account')) {
            return $this->getNewAccountVars();
        }

        if (str_starts_with($templateIdentifier, 'customer_password')
            || str_starts_with($templateIdentifier, 'customer_password_forgot')
            || str_starts_with($templateIdentifier, 'customer_password_remind')
            || str_starts_with($templateIdentifier, 'customer_password_reset')
        ) {
            return $this->getPasswordVars();
        }

        if (str_starts_with($templateIdentifier, 'customer_account_information')) {
            return $this->getAccountChangeVars();
        }

        return $this->getNewAccountVars();
    }

    /**
     * @return array<string, mixed>
     */
    private function getNewAccountVars(): array
    {
        return [
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
                'id' => 1,
            ]),
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
            'back_url' => 'https://example.com/customer/account/',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPasswordVars(): array
    {
        return [
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
                'id' => 1,
                'rp_token' => 'abc123def456ghi789',
            ]),
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getAccountChangeVars(): array
    {
        return [
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
                'id' => 1,
            ]),
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
        ];
    }
}
