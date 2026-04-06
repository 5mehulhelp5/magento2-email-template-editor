<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface ConfigInterface
{
    /**
     * Check whether the email template editor module is enabled
     *
     * @param int $storeId Store ID (0 for default scope)
     * @return bool
     */
    public function isEnabled(int $storeId = 0): bool;
}
