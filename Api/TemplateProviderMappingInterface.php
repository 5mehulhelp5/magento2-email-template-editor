<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface TemplateProviderMappingInterface
{
    /**
     * Get the sample data provider codes applicable to a template identifier
     *
     * @param string $templateIdentifier
     * @return array<string> List of provider codes
     */
    public function getProviderCodes(string $templateIdentifier): array;

    /**
     * Get the template group name for a given template identifier
     *
     * @param string $templateIdentifier
     * @return string Group name (e.g., "order", "customer", "newsletter")
     */
    public function getTemplateGroup(string $templateIdentifier): string;
}
