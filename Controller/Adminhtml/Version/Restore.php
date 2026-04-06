<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Version;

use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterface;
use Hryvinskyi\EmailTemplateEditor\Api\Data\TemplateOverrideInterfaceFactory;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateOverrideRepositoryInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateVersionRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Restore extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::history';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateVersionRepositoryInterface $versionRepository
     * @param TemplateOverrideRepositoryInterface $overrideRepository
     * @param TemplateOverrideInterfaceFactory $overrideFactory
     * @param AuthSession $authSession
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly TemplateVersionRepositoryInterface $versionRepository,
        private readonly TemplateOverrideRepositoryInterface $overrideRepository,
        private readonly TemplateOverrideInterfaceFactory $overrideFactory,
        private readonly AuthSession $authSession
    ) {
        parent::__construct($context);
    }

    /**
     * Restore a version by creating a new draft override from its content
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $versionId = (int)$this->getRequest()->getParam('version_id', 0);

        if (!$versionId) {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Version ID is required.'),
            ]);
        }

        try {
            $version = $this->versionRepository->getById($versionId);
            $templateIdentifier = (string)$version->getTemplateIdentifier();
            $storeId = $version->getStoreId();

            $draft = $this->overrideRepository->getDraft($templateIdentifier, $storeId);

            if ($draft === null) {
                $draft = $this->overrideFactory->create();
                $draft->setTemplateIdentifier($templateIdentifier);
                $draft->setStoreId($storeId);
                $draft->setStatus(TemplateOverrideInterface::STATUS_DRAFT);
            }

            $draft->setTemplateContent((string)$version->getTemplateContent());
            $draft->setTemplateSubject($version->getTemplateSubject());
            $draft->setCustomCss($version->getCustomCss());
            $draft->setTailwindCss($version->getTailwindCss());
            $draft->setThemeId($version->getThemeId());

            $adminUser = $this->authSession->getUser();

            if ($draft->getEntityId() === null && $adminUser !== null) {
                $draft->setCreatedByUserId((int)$adminUser->getId());
                $draft->setCreatedByUsername((string)$adminUser->getUserName());
            }

            if ($adminUser !== null) {
                $draft->setLastEditedByUserId((int)$adminUser->getId());
                $draft->setLastEditedByUsername((string)$adminUser->getUserName());
            }

            $draft->setLastEditedAt((new \DateTime())->format('Y-m-d H:i:s'));
            $this->overrideRepository->save($draft);

            $versionNumber = $version->getVersionNumber();

            return $resultJson->setData([
                'success' => true,
                'entity_id' => $draft->getEntityId(),
                'content' => (string)$version->getTemplateContent(),
                'subject' => $version->getTemplateSubject(),
                'message' => (string)__('Draft created from version v%1. Publish to make it live.', $versionNumber),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
