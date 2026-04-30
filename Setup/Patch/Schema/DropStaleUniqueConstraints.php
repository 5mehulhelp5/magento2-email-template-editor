<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Setup\Patch\Schema;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class DropStaleUniqueConstraints implements SchemaPatchInterface
{
    private const TABLE_NAME = 'hryvinskyi_email_template_override';

    /**
     * @param SchemaSetupInterface $schemaSetup
     */
    public function __construct(
        private readonly SchemaSetupInterface $schemaSetup
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(): self
    {
        $connection = $this->schemaSetup->getConnection();
        $tableName = $this->schemaSetup->getTable(self::TABLE_NAME);

        if (!$connection->isTableExists($tableName)) {
            return $this;
        }

        $indexes = $connection->getIndexList($tableName);

        foreach ($indexes as $indexName => $indexData) {
            if ($indexName === 'PRIMARY') {
                continue;
            }

            if (isset($indexData['type']) && $indexData['type'] === AdapterInterface::INDEX_TYPE_UNIQUE) {
                $connection->dropIndex($tableName, $indexName);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases(): array
    {
        return [];
    }
}
