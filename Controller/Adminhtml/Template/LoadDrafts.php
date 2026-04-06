<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Template;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateOverrideRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class LoadDrafts extends Action implements HttpGetActionInterface
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
     * Load all drafts for a given template identifier and store
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $identifier = (string)$this->getRequest()->getParam('template_identifier', '');
        $storeId = (int)$this->getRequest()->getParam('store_id', 0);

        if ($identifier === '') {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Template identifier is required.'),
            ]);
        }

        try {
            $drafts = $this->overrideRepository->getDrafts($identifier, $storeId);
            $result = [];

            /** @var TemplateOverrideInterface $draft */
            foreach ($drafts as $draft) {
                $result[] = [
                    'entity_id' => $draft->getEntityId(),
                    'draft_name' => $draft->getDraftName(),
                    'template_subject' => $draft->getTemplateSubject(),
                    'status' => $draft->getStatus(),
                    'active_from' => $draft->getActiveFrom(),
                    'active_to' => $draft->getActiveTo(),
                    'created_by_username' => $draft->getCreatedByUsername(),
                    'last_edited_by' => $draft->getLastEditedByUsername(),
                    'updated_at' => $draft->getUpdatedAt(),
                    'created_at' => $draft->getCreatedAt(),
                ];
            }

            return $resultJson->setData([
                'success' => true,
                'drafts' => $result,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
