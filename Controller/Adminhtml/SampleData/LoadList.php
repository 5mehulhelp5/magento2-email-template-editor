<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\SampleData;

use Hryvinskyi\EmailTemplateEditor\Api\SampleDataProviderPoolInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateProviderMappingInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class LoadList extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @var array<string, string>
     */
    private const GROUP_LABELS = [
        'order' => 'Order',
        'invoice' => 'Invoice',
        'shipment' => 'Shipment',
        'creditmemo' => 'Credit Memo',
        'customer' => 'Customer',
        'newsletter' => 'Subscriber',
        'contact' => 'Contact',
        'checkout' => 'Order',
        'header_footer' => 'Data Source',
    ];

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SampleDataProviderPoolInterface $providerPool
     * @param TemplateProviderMappingInterface $templateProviderMapping
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly SampleDataProviderPoolInterface $providerPool,
        private readonly TemplateProviderMappingInterface $templateProviderMapping
    ) {
        parent::__construct($context);
    }

    /**
     * Load list of available sample data providers for a given template
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $templateIdentifier = (string)$this->getRequest()->getParam('template_identifier', '');
            $providers = [];

            $availableProviders = $templateIdentifier !== ''
                ? $this->providerPool->getProvidersForTemplate($templateIdentifier)
                : $this->providerPool->getAllProviders();

            foreach ($availableProviders as $provider) {
                $providers[] = [
                    'code' => $provider->getCode(),
                    'label' => $provider->getLabel(),
                    'supports_entity_search' => $provider->supportsEntitySearch(),
                ];
            }

            $dataSourceLabel = 'Data Source';

            if ($templateIdentifier !== '') {
                $group = $this->templateProviderMapping->getTemplateGroup($templateIdentifier);
                $dataSourceLabel = self::GROUP_LABELS[$group] ?? 'Data Source';
            }

            return $resultJson->setData([
                'success' => true,
                'providers' => $providers,
                'data_source_label' => (string)__($dataSourceLabel),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
