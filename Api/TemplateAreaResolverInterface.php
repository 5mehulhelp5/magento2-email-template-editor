<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface TemplateAreaResolverInterface
{
    /**
     * Resolve the Magento application area for a given template ID
     *
     * @param string $templateId Template identifier (e.g., "sales_email_order_template")
     * @return string Application area code (e.g., "frontend", "adminhtml")
     */
    public function resolve(string $templateId): string;
}
