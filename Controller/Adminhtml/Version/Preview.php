<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Version;

use Hryvinskyi\EmailTemplateEditor\Api\TemplateRendererInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateVersionRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Preview extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::history';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateRendererInterface $templateRenderer
     * @param TemplateVersionRepositoryInterface $versionRepository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly TemplateRendererInterface $templateRenderer,
        private readonly TemplateVersionRepositoryInterface $versionRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Render a preview of a specific version's template content
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
            $content = $version->getTemplateContent();

            if ($content === null || $content === '') {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Version has no template content.'),
                ]);
            }

            $html = $this->templateRenderer->render(
                $content,
                [],
                $version->getStoreId(),
                $version->getCustomCss(),
                $version->getTailwindCss(),
                $version->getTemplateIdentifier()
            );

            return $resultJson->setData([
                'success' => true,
                'html' => $html,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
