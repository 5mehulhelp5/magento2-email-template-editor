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

class GenericMockBuilder implements MockVariableBuilderInterface
{
    /**
     * @inheritDoc
     */
    public function build(string $templateIdentifier, int $storeId): array
    {
        return [
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
            ]),
            'customer_name' => 'John Doe',
            'customer_email' => 'john.doe@example.com',
        ];
    }
}
