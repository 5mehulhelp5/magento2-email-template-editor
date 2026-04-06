<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Theme;

use Hryvinskyi\EmailTemplateEditor\Api\ThemeRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::themes';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ThemeRepositoryInterface $themeRepository
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ThemeRepositoryInterface $themeRepository
    ) {
        parent::__construct($context);
    }

    /**
     * Delete a theme by its ID
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();
        $themeId = (int)$this->getRequest()->getParam('theme_id', 0);

        if (!$themeId) {
            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Theme ID is required.'),
            ]);
        }

        try {
            $theme = $this->themeRepository->getById($themeId);

            if ($theme->getIsDefault()) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Cannot delete the default theme.'),
                ]);
            }

            $this->themeRepository->delete($theme);

            return $resultJson->setData([
                'success' => true,
                'message' => (string)__('Theme deleted successfully.'),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
