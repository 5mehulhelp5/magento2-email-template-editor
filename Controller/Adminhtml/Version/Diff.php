<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Version;

use Hryvinskyi\EmailTemplateEditor\Api\TemplateVersionRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Diff extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::history';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateVersionRepositoryInterface $versionRepository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly TemplateVersionRepositoryInterface $versionRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Load two version entries for client-side diff comparison
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $versionIdA = (int)$this->getRequest()->getParam('version_id_a', 0);
        $versionIdB = (int)$this->getRequest()->getParam('version_id_b', 0);

        if (!$versionIdA || !$versionIdB) {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Both version IDs are required for comparison.'),
            ]);
        }

        try {
            $versionA = $this->versionRepository->getById($versionIdA);
            $versionB = $this->versionRepository->getById($versionIdB);

            return $resultJson->setData([
                'success' => true,
                'version_a' => [
                    'version_id' => $versionA->getVersionId(),
                    'version_number' => $versionA->getVersionNumber(),
                    'content' => $versionA->getTemplateContent(),
                    'subject' => $versionA->getTemplateSubject(),
                    'custom_css' => $versionA->getCustomCss(),
                    'tailwind_css' => $versionA->getTailwindCss(),
                    'version_comment' => $versionA->getVersionComment(),
                    'published_at' => $versionA->getPublishedAt(),
                ],
                'version_b' => [
                    'version_id' => $versionB->getVersionId(),
                    'version_number' => $versionB->getVersionNumber(),
                    'content' => $versionB->getTemplateContent(),
                    'subject' => $versionB->getTemplateSubject(),
                    'custom_css' => $versionB->getCustomCss(),
                    'tailwind_css' => $versionB->getTailwindCss(),
                    'version_comment' => $versionB->getVersionComment(),
                    'published_at' => $versionB->getPublishedAt(),
                ],
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
