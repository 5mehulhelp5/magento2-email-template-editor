<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\SampleData;

use Hryvinskyi\EmailTemplateEditor\Api\SampleDataProviderPoolInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class GetVariables extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param SampleDataProviderPoolInterface $providerPool
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly SampleDataProviderPoolInterface $providerPool
    ) {
        parent::__construct($context);
    }

    /**
     * Get template variables from a specific sample data provider
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $providerCode = (string)$this->getRequest()->getParam('provider_code', 'mock');
            $templateIdentifier = (string)$this->getRequest()->getParam('template_identifier', '');
            $storeId = (int)$this->getRequest()->getParam('store_id', 0);
            $entityId = $this->getRequest()->getParam('entity_id');

            $provider = $this->providerPool->getProvider($providerCode);
            $entityIdValue = $entityId !== null && $entityId !== '' ? (string)$entityId : null;
            $variables = $provider->getVariables($templateIdentifier, $storeId, $entityIdValue);

            return $resultJson->setData([
                'success' => true,
                'variables' => $variables,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
