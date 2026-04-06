<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Template;

use Hryvinskyi\EmailTemplateEditor\Api\TemplateOverrideRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class DeleteDraft extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateOverrideRepositoryInterface $overrideRepository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly TemplateOverrideRepositoryInterface $overrideRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Delete a draft template override
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $entityId = (int)$this->getRequest()->getParam('entity_id', 0);
            $templateIdentifier = (string)$this->getRequest()->getParam('template_identifier', '');
            $storeId = (int)$this->getRequest()->getParam('store_id', 0);

            if ($entityId > 0) {
                $draft = $this->overrideRepository->getById($entityId);
            } elseif ($templateIdentifier !== '') {
                $draft = $this->overrideRepository->getDraft($templateIdentifier, $storeId);
            } else {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Entity ID or template identifier is required.'),
                ]);
            }

            if ($draft === null) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('No draft found.'),
                ]);
            }

            $this->overrideRepository->delete($draft);

            return $resultJson->setData([
                'success' => true,
                'message' => (string)__('Draft deleted successfully.'),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
