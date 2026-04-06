<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\SampleDataProviderInterface;
use Hryvinskyi\EmailTemplateEditor\Api\SampleDataProviderPoolInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateProviderMappingInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class SampleDataProviderPool implements SampleDataProviderPoolInterface
{
    /**
     * @param TemplateProviderMappingInterface $templateProviderMapping
     * @param array<string, SampleDataProviderInterface> $providers
     */
    public function __construct(
        private readonly TemplateProviderMappingInterface $templateProviderMapping,
        private readonly array $providers = []
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getProvider(string $code): SampleDataProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->getCode() === $code) {
                return $provider;
            }
        }

        throw new NoSuchEntityException(__('Sample data provider with code "%1" does not exist.', $code));
    }

    /**
     * @inheritDoc
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * @inheritDoc
     */
    public function getProvidersForTemplate(string $templateIdentifier): array
    {
        $providerCodes = $this->templateProviderMapping->getProviderCodes($templateIdentifier);
        $result = [];

        foreach ($providerCodes as $code) {
            foreach ($this->providers as $key => $provider) {
                if ($provider->getCode() === $code) {
                    $result[$key] = $provider;
                    break;
                }
            }
        }

        return $result;
    }
}
