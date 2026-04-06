<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Template;

use Hryvinskyi\EmailTemplateEditor\Api\TemplateOverrideRepositoryInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplatePublisherInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Publish extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplatePublisherInterface $templatePublisher
     * @param TemplateOverrideRepositoryInterface $overrideRepository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly TemplatePublisherInterface $templatePublisher,
        private readonly TemplateOverrideRepositoryInterface $overrideRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Publish a draft override, optionally scheduling it for future activation
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $entityId = (int)$this->getRequest()->getParam('entity_id', 0);
            $versionComment = $this->getRequest()->getParam('version_comment');
            $scheduledAt = $this->getRequest()->getParam('scheduled_at');

            if (!$entityId) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Override ID is required.'),
                ]);
            }

            $versionCommentValue = $versionComment !== null && $versionComment !== '' ? (string)$versionComment : null;

            if ($scheduledAt !== null && $scheduledAt !== '') {
                $scheduledTime = strtotime((string)$scheduledAt);
                if ($scheduledTime !== false && $scheduledTime > time()) {
                    $this->templatePublisher->schedulePublish($entityId, (string)$scheduledAt, $versionCommentValue);

                    return $resultJson->setData([
                        'success' => true,
                        'message' => (string)__('Template scheduled for publication at %1.', $scheduledAt),
                    ]);
                }
            }

            $publishedEntityId = $this->templatePublisher->publish($entityId, $versionCommentValue);

            return $resultJson->setData([
                'success' => true,
                'entity_id' => $publishedEntityId,
                'message' => (string)__('Template published successfully.'),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
