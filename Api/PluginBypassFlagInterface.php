<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

interface PluginBypassFlagInterface
{
    /**
     * Check whether the plugin should be bypassed
     *
     * @return bool
     */
    public function isBypassed(): bool;

    /**
     * Enable bypass mode
     *
     * @return void
     */
    public function enable(): void;

    /**
     * Disable bypass mode
     *
     * @return void
     */
    public function disable(): void;
}
