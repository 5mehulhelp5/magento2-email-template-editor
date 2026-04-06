<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Template;

use Hryvinskyi\EmailTemplateEditor\Api\ScheduleConflictDetectorInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class CheckScheduleConflict extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ScheduleConflictDetectorInterface $conflictDetector
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ScheduleConflictDetectorInterface $conflictDetector
    ) {
        parent::__construct($context);
    }

    /**
     * Check for schedule conflicts with existing overrides
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $identifier = (string)$this->getRequest()->getParam('template_identifier', '');
        $storeId = (int)$this->getRequest()->getParam('store_id', 0);
        $activeFrom = $this->getRequest()->getParam('active_from');
        $activeTo = $this->getRequest()->getParam('active_to');
        $excludeEntityId = $this->getRequest()->getParam('exclude_entity_id');

        if ($identifier === '') {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Template identifier is required.'),
            ]);
        }

        try {
            $conflicts = $this->conflictDetector->detect(
                $identifier,
                $storeId,
                $activeFrom !== '' ? $activeFrom : null,
                $activeTo !== '' ? $activeTo : null,
                $excludeEntityId !== null && $excludeEntityId !== '' ? (int)$excludeEntityId : null
            );

            return $resultJson->setData([
                'success' => true,
                'has_conflicts' => count($conflicts) > 0,
                'conflicts' => $conflicts,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
