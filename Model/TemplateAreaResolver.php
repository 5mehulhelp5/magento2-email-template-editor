<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\TemplateAreaResolverInterface;
use Magento\Email\Model\Template\Config as EmailConfig;

class TemplateAreaResolver implements TemplateAreaResolverInterface
{
    /**
     * @param EmailConfig $emailConfig
     */
    public function __construct(
        private readonly EmailConfig $emailConfig
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(string $templateId): string
    {
        try {
            $parts = $this->emailConfig->parseTemplateIdParts($templateId);

            return $this->emailConfig->getTemplateArea($parts['templateId']);
        } catch (\Exception) {
            return 'frontend';
        }
    }
}
