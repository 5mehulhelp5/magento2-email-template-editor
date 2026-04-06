<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\ThemeJsonValidatorInterface;

class ThemeJsonValidator implements ThemeJsonValidatorInterface
{
    /**
     * Required top-level sections in the theme JSON
     */
    private const REQUIRED_SECTIONS = ['tokens', 'elements', 'utilities'];

    /**
     * Required keys within the tokens section
     */
    private const REQUIRED_TOKEN_KEYS = ['colors', 'spacing', 'fontSize'];

    /**
     * Accumulated validation errors
     *
     * @var array<string>
     */
    private array $errors = [];

    /**
     * @inheritDoc
     */
    public function validate(string $json): bool
    {
        $this->errors = [];
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = 'Invalid JSON: ' . json_last_error_msg();
            return false;
        }

        if (!is_array($data)) {
            $this->errors[] = 'Theme JSON must be an object.';
            return false;
        }

        foreach (self::REQUIRED_SECTIONS as $section) {
            if (!isset($data[$section]) || !is_array($data[$section])) {
                $this->errors[] = sprintf('Missing or invalid required section: "%s".', $section);
            }
        }

        if (isset($data['tokens']) && is_array($data['tokens'])) {
            $this->validateTokenKeys($data['tokens']);
        }

        return empty($this->errors);
    }

    /**
     * @inheritDoc
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Validate required keys within the tokens section
     *
     * @param array<string, mixed> $tokens
     * @return void
     */
    private function validateTokenKeys(array $tokens): void
    {
        foreach (self::REQUIRED_TOKEN_KEYS as $key) {
            if (!isset($tokens[$key]) || !is_array($tokens[$key])) {
                $this->errors[] = sprintf('Missing or invalid token key: "tokens.%s".', $key);
            }
        }

        $validTokenValueTypes = ['colors', 'spacing', 'fontSize', 'fontFamily', 'borderRadius',
            'lineHeight', 'fontWeight', 'letterSpacing', 'maxWidth', 'boxShadow', 'opacity', 'zIndex'];

        foreach ($validTokenValueTypes as $tokenKey) {
            if (isset($tokens[$tokenKey]) && !is_array($tokens[$tokenKey])) {
                $this->errors[] = sprintf('Token key "tokens.%s" must be an object.', $tokenKey);
            }
        }

        if (isset($tokens['googleFonts']) && !is_array($tokens['googleFonts'])) {
            $this->errors[] = 'Token key "tokens.googleFonts" must be an array.';
        }
    }
}
