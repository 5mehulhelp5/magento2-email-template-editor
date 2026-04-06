<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Template;

use Hryvinskyi\EmailTemplateEditor\Api\TemplatePublisherInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class UpdateSchedule extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplatePublisherInterface $templatePublisher
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly TemplatePublisherInterface $templatePublisher
    ) {
        parent::__construct($context);
    }

    /**
     * Update the schedule of a published override without creating a draft
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $entityId = (int)$this->getRequest()->getParam('entity_id', 0);

            if (!$entityId) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Override ID is required.'),
                ]);
            }

            $activeFrom = $this->getRequest()->getParam('active_from');
            $activeTo = $this->getRequest()->getParam('active_to');

            $activeFromValue = $activeFrom !== null && $activeFrom !== '' ? (string)$activeFrom : null;
            $activeToValue = $activeTo !== null && $activeTo !== '' ? (string)$activeTo : null;

            $this->templatePublisher->updateSchedule($entityId, $activeFromValue, $activeToValue);

            return $resultJson->setData([
                'success' => true,
                'entity_id' => $entityId,
                'active_from' => $activeFromValue ?? '',
                'active_to' => $activeToValue ?? '',
                'message' => (string)__('Schedule updated successfully.'),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
