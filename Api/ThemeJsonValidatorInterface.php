<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface ThemeJsonValidatorInterface
{
    /**
     * Validate a theme JSON configuration string
     *
     * @param string $json JSON string to validate
     * @return bool True if valid, false otherwise
     */
    public function validate(string $json): bool;

    /**
     * Get validation errors from the last validate() call
     *
     * @return array<string> List of validation error messages
     */
    public function getErrors(): array;
}
