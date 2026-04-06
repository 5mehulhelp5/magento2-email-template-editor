<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Template;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterface;
use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterfaceFactory;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateOverrideRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class DuplicateDraft extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateOverrideRepositoryInterface $overrideRepository
     * @param TemplateOverrideInterfaceFactory $overrideFactory
     * @param AuthSession $authSession
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly TemplateOverrideRepositoryInterface $overrideRepository,
        private readonly TemplateOverrideInterfaceFactory $overrideFactory,
        private readonly AuthSession $authSession
    ) {
        parent::__construct($context);
    }

    /**
     * Duplicate an existing draft by entity ID
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $entityId = (int)$this->getRequest()->getParam('entity_id', 0);

        if ($entityId === 0) {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Entity ID is required.'),
            ]);
        }

        try {
            $source = $this->overrideRepository->getById($entityId);

            $clone = $this->overrideFactory->create();
            $clone->setTemplateIdentifier($source->getTemplateIdentifier());
            $clone->setStoreId($source->getStoreId());
            $clone->setStatus(TemplateOverrideInterface::STATUS_DRAFT);
            $clone->setTemplateContent($source->getTemplateContent() ?? '');
            $clone->setTemplateSubject($source->getTemplateSubject());
            $clone->setCustomCss($source->getCustomCss());
            $clone->setTailwindCss($source->getTailwindCss());
            $clone->setThemeId($source->getThemeId());
            $clone->setDraftName(($source->getDraftName() ?? 'Draft') . ' (copy)');

            $adminUser = $this->authSession->getUser();

            if ($adminUser !== null) {
                $clone->setCreatedByUserId((int)$adminUser->getId());
                $clone->setCreatedByUsername((string)$adminUser->getUserName());
                $clone->setLastEditedByUserId((int)$adminUser->getId());
                $clone->setLastEditedByUsername((string)$adminUser->getUserName());
            }

            $clone->setLastEditedAt((new \DateTime())->format('Y-m-d H:i:s'));
            $this->overrideRepository->save($clone);

            return $resultJson->setData([
                'success' => true,
                'entity_id' => $clone->getEntityId(),
                'message' => (string)__('Draft duplicated successfully.'),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
