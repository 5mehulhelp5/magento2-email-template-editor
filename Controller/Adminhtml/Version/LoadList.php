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
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class LoadList extends Action implements HttpGetActionInterface
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
     * Load the version history list for a template
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $templateIdentifier = (string)$this->getRequest()->getParam('template_identifier', '');
        $storeId = (int)$this->getRequest()->getParam('store_id', 0);

        if ($templateIdentifier === '') {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Template identifier is required.'),
            ]);
        }

        try {
            $versions = $this->versionRepository->getVersionList($templateIdentifier, $storeId);
            $data = [];

            foreach ($versions as $version) {
                $data[] = [
                    'version_id' => $version->getVersionId(),
                    'version_number' => $version->getVersionNumber(),
                    'version_comment' => $version->getVersionComment(),
                    'admin_user_id' => $version->getAdminUserId(),
                    'admin_username' => $version->getAdminUsername(),
                    'published_at' => $version->getPublishedAt(),
                    'created_at' => $version->getCreatedAt(),
                ];
            }

            return $resultJson->setData([
                'success' => true,
                'versions' => $data,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
