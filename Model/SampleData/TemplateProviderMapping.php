<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\SampleData;

use Hryvinskyi\EmailTemplateEditor\Api\TemplateSampleDataMapperInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateProviderMappingInterface;

class TemplateProviderMapping implements TemplateProviderMappingInterface
{
    /**
     * @param TemplateSampleDataMapperInterface $templateMapper
     * @param array<string, string> $templateGroupMap
     * @param array<string, array<int, string>> $groups
     */
    public function __construct(
        private readonly TemplateSampleDataMapperInterface $templateMapper,
        private readonly array $templateGroupMap = [],
        private readonly array $groups = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProviderCodes(string $templateIdentifier): array
    {
        $group = $this->getTemplateGroup($templateIdentifier);

        return $this->groups[$group] ?? [];
    }

    /**
     * @inheritDoc
     */
    public function getTemplateGroup(string $templateIdentifier): string
    {
        if (isset($this->templateGroupMap[$templateIdentifier])) {
            return $this->templateGroupMap[$templateIdentifier];
        }

        return $this->templateMapper->getCategory($templateIdentifier);
    }
}
