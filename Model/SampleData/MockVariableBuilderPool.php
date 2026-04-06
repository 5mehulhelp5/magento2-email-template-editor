<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\SampleData;

use Hryvinskyi\EmailTemplateEditor\Api\MockVariableBuilderInterface;
use Hryvinskyi\EmailTemplateEditor\Api\MockVariableBuilderPoolInterface;

class MockVariableBuilderPool implements MockVariableBuilderPoolInterface
{
    /**
     * @param MockVariableBuilderInterface $fallbackBuilder
     * @param array<string, MockVariableBuilderInterface> $builders
     */
    public function __construct(
        private readonly MockVariableBuilderInterface $fallbackBuilder,
        private readonly array $builders = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getBuilder(string $category): MockVariableBuilderInterface
    {
        return $this->builders[$category] ?? $this->fallbackBuilder;
    }
}
