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

class RenameDraft extends Action implements HttpPostActionInterface
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
     * Rename a draft by entity ID
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $entityId = (int)$this->getRequest()->getParam('entity_id', 0);
        $draftName = (string)$this->getRequest()->getParam('draft_name', '');

        if ($entityId === 0) {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Entity ID is required.'),
            ]);
        }

        if ($draftName === '') {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Draft name is required.'),
            ]);
        }

        try {
            $override = $this->overrideRepository->getById($entityId);
            $override->setDraftName($draftName);
            $this->overrideRepository->save($override);

            return $resultJson->setData([
                'success' => true,
                'message' => (string)__('Draft renamed successfully.'),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
