<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model;

use Hryvinskyi\EmailTemplateEditor\Api\VariableChooserProviderInterface;
use Magento\Email\Model\Template\Config as EmailConfig;
use Magento\Email\Model\TemplateFactory;
use Magento\Variable\Model\ResourceModel\Variable\CollectionFactory as CustomVariableCollectionFactory;
use Magento\Variable\Model\Source\Variables as ConfigVariables;
use Psr\Log\LoggerInterface;

class VariableChooserProvider implements VariableChooserProviderInterface
{
    /**
     * @param ConfigVariables $configVariables
     * @param CustomVariableCollectionFactory $customVariableCollectionFactory
     * @param EmailConfig $emailConfig
     * @param TemplateFactory $templateFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigVariables $configVariables,
        private readonly CustomVariableCollectionFactory $customVariableCollectionFactory,
        private readonly EmailConfig $emailConfig,
        private readonly TemplateFactory $templateFactory,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getVariableGroups(string $templateId, int $storeId = 0): array
    {
        $groups = [];

        $systemVariables = $this->getSystemVariables();
        if (!empty($systemVariables)) {
            $groups['System Variables'] = $systemVariables;
        }

        $customVariables = $this->getCustomVariables();
        if (!empty($customVariables)) {
            $groups['Custom Variables'] = $customVariables;
        }

        $templateVariables = $this->getTemplateVariables($templateId);
        if (!empty($templateVariables)) {
            $groups['Template Variables'] = $templateVariables;
        }

        return $groups;
    }

    /**
     * Get system configuration variables
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function getSystemVariables(): array
    {
        $variables = [];

        try {
            $configVars = $this->configVariables->toOptionArray(true);

            foreach ($configVars as $group) {
                if (!isset($group['value']) || !is_array($group['value'])) {
                    continue;
                }

                foreach ($group['value'] as $variable) {
                    if (isset($variable['value'], $variable['label'])) {
                        $variables[] = [
                            'label' => (string)$variable['label'],
                            'value' => (string)$variable['value'],
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to load system variables: ' . $e->getMessage());
        }

        return $variables;
    }

    /**
     * Get custom variables defined in admin
     *
     * @return array<int, array{label: string, value: string}>
     */
    private function getCustomVariables(): array
    {
        $variables = [];

        try {
            $collection = $this->customVariableCollectionFactory->create();

            foreach ($collection as $variable) {
                $code = $variable->getCode();
                $name = $variable->getName();

                $variables[] = [
                    'label' => (string)$name,
                    'value' => '{{customVar code=' . $code . '}}',
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('Failed to load custom variables: ' . $e->getMessage());
        }

        return $variables;
    }

    /**
     * Get template-specific variables from the template's @vars annotation
     *
     * @param string $templateId
     * @return array<int, array{label: string, value: string}>
     */
    private function getTemplateVariables(string $templateId): array
    {
        $variables = [];

        try {
            $template = $this->templateFactory->create();
            $template->setForcedArea($templateId);
            $template->loadDefault($templateId);

            $templateVariables = $template->getData('orig_template_variables');

            if (empty($templateVariables)) {
                return $variables;
            }

            $parsedVars = json_decode($templateVariables, true);

            if (!is_array($parsedVars)) {
                return $variables;
            }

            foreach ($parsedVars as $varDirective => $varLabel) {
                $directive = (string)$varDirective;

                if (strpos($directive, '{{') !== 0) {
                    $directive = '{{' . $directive . '}}';
                }

                $variables[] = [
                    'label' => (string)$varLabel,
                    'value' => $directive,
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to load template variables for "' . $templateId . '": ' . $e->getMessage()
            );
        }

        return $variables;
    }
}
