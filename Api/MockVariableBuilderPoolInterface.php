<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface MockVariableBuilderPoolInterface
{
    /**
     * Get the mock variable builder for a given template category
     *
     * @param string $category Category code (e.g., "order", "customer")
     * @return MockVariableBuilderInterface
     */
    public function getBuilder(string $category): MockVariableBuilderInterface;
}
