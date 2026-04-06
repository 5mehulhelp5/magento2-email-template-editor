<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\PluginBypassFlagInterface;

class PluginBypassFlag implements PluginBypassFlagInterface
{
    /**
     * @var bool
     */
    private bool $bypassed = false;

    /**
     * {@inheritDoc}
     */
    public function isBypassed(): bool
    {
        return $this->bypassed;
    }

    /**
     * {@inheritDoc}
     */
    public function enable(): void
    {
        $this->bypassed = true;
    }

    /**
     * {@inheritDoc}
     */
    public function disable(): void
    {
        $this->bypassed = false;
    }
}
