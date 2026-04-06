<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Variable;

use Hryvinskyi\EmailTemplateEditor\Api\VariableChooserProviderInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class LoadGroups extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param VariableChooserProviderInterface $variableChooserProvider
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly VariableChooserProviderInterface $variableChooserProvider
    ) {
        parent::__construct($context);
    }

    /**
     * Load variable groups for the variable chooser panel
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $templateId = (string)$this->getRequest()->getParam('template_id', '');
            $storeId = (int)$this->getRequest()->getParam('store_id', 0);

            $groups = $this->variableChooserProvider->getVariableGroups($templateId, $storeId);

            return $resultJson->setData([
                'success' => true,
                'groups' => $groups,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
