<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\Theme;

use Hryvinskyi\EmailTemplateEditor\Model\Theme;
use Hryvinskyi\EmailTemplateEditor\Model\ResourceModel\Theme as ThemeResource;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize collection model and resource model mapping
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(Theme::class, ThemeResource::class);
    }
}
