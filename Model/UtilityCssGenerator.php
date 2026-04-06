<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\UtilityCssGeneratorInterface;

class UtilityCssGenerator implements UtilityCssGeneratorInterface
{
    /**
     * Mapping of token section keys to their CSS custom property prefixes
     */
    private const TOKEN_PREFIX_MAP = [
        'colors' => 'color',
        'spacing' => 'spacing',
        'fontSize' => 'font-size',
        'fontFamily' => 'font-family',
        'borderRadius' => 'radius',
        'lineHeight' => 'leading',
        'fontWeight' => 'font-weight',
        'letterSpacing' => 'tracking',
        'maxWidth' => 'max-width',
        'boxShadow' => 'shadow',
        'opacity' => 'opacity',
        'zIndex' => 'z-index',
    ];

    /**
     * {@inheritDoc}
     */
    public function generate(string $themeJson): string
    {
        $data = json_decode($themeJson, true);

        if (!is_array($data)) {
            return '';
        }

        $css = [];

        if (!empty($data['tokens']['googleFonts']) && is_array($data['tokens']['googleFonts'])) {
            foreach ($data['tokens']['googleFonts'] as $font) {
                $css[] = sprintf(
                    "@import url('https://fonts.googleapis.com/css2?family=%s&display=swap');",
                    urlencode((string)$font)
                );
            }
        }

        $themeVariables = $this->generateThemeVariables($data);
        if ($themeVariables !== '') {
            $css[] = $themeVariables;
        }

        if (!empty($data['elements']) && is_array($data['elements'])) {
            $elementCss = $this->generateElementCss($data['elements']);
            if ($elementCss !== '') {
                $css[] = $elementCss;
            }
        }

        if (!empty($data['utilities']) && is_array($data['utilities'])) {
            $utilityCss = $this->generateUtilityCss($data['utilities']);
            if ($utilityCss !== '') {
                $css[] = $utilityCss;
            }
        }

        if (!empty($data['tokens']['colors']) && is_array($data['tokens']['colors'])) {
            $colorCss = $this->generateColorUtilities($data['tokens']['colors']);
            if ($colorCss !== '') {
                $css[] = $colorCss;
            }
        }

        if (!empty($data['tokens']['spacing']) && is_array($data['tokens']['spacing'])) {
            $spacingCss = $this->generateSpacingUtilities($data['tokens']['spacing']);
            if ($spacingCss !== '') {
                $css[] = $spacingCss;
            }
        }

        if (!empty($data['tokens']['fontSize']) && is_array($data['tokens']['fontSize'])) {
            $fontSizeCss = $this->generateFontSizeUtilities($data['tokens']['fontSize']);
            if ($fontSizeCss !== '') {
                $css[] = $fontSizeCss;
            }
        }

        return implode("\n\n", array_filter($css));
    }

    /**
     * Generate CSS custom properties in a @theme block from token definitions
     *
     * @param array<string, mixed> $data
     * @return string
     */
    private function generateThemeVariables(array $data): string
    {
        if (empty($data['tokens']) || !is_array($data['tokens'])) {
            return '';
        }

        $variables = [];

        foreach (self::TOKEN_PREFIX_MAP as $tokenKey => $cssPrefix) {
            if (empty($data['tokens'][$tokenKey]) || !is_array($data['tokens'][$tokenKey])) {
                continue;
            }

            foreach ($data['tokens'][$tokenKey] as $name => $value) {
                $safeName = $this->escapeClassName((string)$name);
                $safeValue = $this->escapeValue((string)$value);
                $variables[] = sprintf('    --%s-%s: %s;', $cssPrefix, $safeName, $safeValue);
            }
        }

        if (empty($variables)) {
            return '';
        }

        return "@theme {\n" . implode("\n", $variables) . "\n}";
    }

    /**
     * Generate CSS for element selectors
     *
     * @param array<string, array<string, string>> $elements
     * @return string
     */
    private function generateElementCss(array $elements): string
    {
        $css = [];

        foreach ($elements as $selector => $properties) {
            if (!is_array($properties) || empty($properties)) {
                continue;
            }

            $rules = [];
            foreach ($properties as $property => $value) {
                $rules[] = sprintf('    %s: %s;', $this->escapeProperty($property), $this->escapeValue($value));
            }

            $css[] = sprintf("%s {\n%s\n}", $this->escapeSelector($selector), implode("\n", $rules));
        }

        return implode("\n\n", $css);
    }

    /**
     * Generate CSS for utility classes
     *
     * @param array<string, array<string, string>> $utilities
     * @return string
     */
    private function generateUtilityCss(array $utilities): string
    {
        $css = [];

        foreach ($utilities as $className => $properties) {
            if (!is_array($properties) || empty($properties)) {
                continue;
            }

            $rules = [];
            foreach ($properties as $property => $value) {
                $rules[] = sprintf('    %s: %s;', $this->escapeProperty($property), $this->escapeValue($value));
            }

            $css[] = sprintf(".%s {\n%s\n}", $this->escapeClassName($className), implode("\n", $rules));
        }

        return implode("\n\n", $css);
    }

    /**
     * Generate text and background color utility classes from color tokens
     *
     * @param array<string, string> $colors
     * @return string
     */
    private function generateColorUtilities(array $colors): string
    {
        $css = [];

        foreach ($colors as $name => $value) {
            $safeName = $this->escapeClassName((string)$name);
            $safeValue = $this->escapeValue((string)$value);

            $css[] = sprintf(".text-%s {\n    color: %s;\n}", $safeName, $safeValue);
            $css[] = sprintf(".bg-%s {\n    background-color: %s;\n}", $safeName, $safeValue);
        }

        return implode("\n\n", $css);
    }

    /**
     * Generate margin and padding utility classes from spacing tokens
     *
     * @param array<string, string> $spacingMap
     * @return string
     */
    private function generateSpacingUtilities(array $spacingMap): string
    {
        $css = [];
        $prefixes = [
            'm' => 'margin',
            'mx' => ['margin-left', 'margin-right'],
            'my' => ['margin-top', 'margin-bottom'],
            'p' => 'padding',
            'px' => ['padding-left', 'padding-right'],
            'py' => ['padding-top', 'padding-bottom'],
        ];

        foreach ($spacingMap as $key => $value) {
            $safeKey = $this->escapeClassName((string)$key);
            $safeValue = $this->escapeValue((string)$value);

            foreach ($prefixes as $prefix => $prop) {
                if (is_array($prop)) {
                    $rules = [];
                    foreach ($prop as $p) {
                        $rules[] = sprintf('    %s: %s;', $p, $safeValue);
                    }
                    $css[] = sprintf(".%s-%s {\n%s\n}", $prefix, $safeKey, implode("\n", $rules));
                } else {
                    $css[] = sprintf(".%s-%s {\n    %s: %s;\n}", $prefix, $safeKey, $prop, $safeValue);
                }
            }
        }

        return implode("\n\n", $css);
    }

    /**
     * Generate font-size utility classes from fontSize tokens
     *
     * @param array<string, string> $fontSizes
     * @return string
     */
    private function generateFontSizeUtilities(array $fontSizes): string
    {
        $css = [];

        foreach ($fontSizes as $name => $value) {
            $safeName = $this->escapeClassName((string)$name);
            $safeValue = $this->escapeValue((string)$value);
            $css[] = sprintf(".text-%s {\n    font-size: %s;\n}", $safeName, $safeValue);
        }

        return implode("\n\n", $css);
    }

    /**
     * Escape a CSS selector to prevent injection
     *
     * @param string $selector
     * @return string
     */
    private function escapeSelector(string $selector): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-\s,.*>#:+~\[\]=\'"()]/', '', $selector) ?? $selector;
    }

    /**
     * Escape a CSS class name to prevent injection
     *
     * @param string $className
     * @return string
     */
    private function escapeClassName(string $className): string
    {
        return preg_replace('/[^a-zA-Z0-9_\-]/', '-', $className) ?? $className;
    }

    /**
     * Escape a CSS property name to prevent injection
     *
     * @param string $property
     * @return string
     */
    private function escapeProperty(string $property): string
    {
        return preg_replace('/[^a-zA-Z0-9\-]/', '', $property) ?? $property;
    }

    /**
     * Escape a CSS value to prevent injection
     *
     * @param string $value
     * @return string
     */
    private function escapeValue(string $value): string
    {
        return preg_replace('/[;\{\}]/', '', $value) ?? $value;
    }
}
