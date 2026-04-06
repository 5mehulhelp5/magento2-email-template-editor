<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Api;

use Magento\Framework\Exception\NoSuchEntityException;

interface SampleDataProviderPoolInterface
{
    /**
     * Get a specific sample data provider by its code
     *
     * @param string $code Provider code
     * @return SampleDataProviderInterface
     * @throws NoSuchEntityException
     */
    public function getProvider(string $code): SampleDataProviderInterface;

    /**
     * Get all registered sample data providers
     *
     * @return array<string, SampleDataProviderInterface>
     */
    public function getAllProviders(): array;

    /**
     * Get providers applicable to a specific template identifier
     *
     * @param string $templateIdentifier
     * @return array<string, SampleDataProviderInterface>
     */
    public function getProvidersForTemplate(string $templateIdentifier): array;
}
