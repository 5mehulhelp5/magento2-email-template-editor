<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Theme;

use Hryvinskyi\EmailTemplateEditor\Api\Data\ThemeInterfaceFactory;
use Hryvinskyi\EmailTemplateEditor\Api\ThemeJsonValidatorInterface;
use Hryvinskyi\EmailTemplateEditor\Api\ThemeRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

class Import extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::themes';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param ThemeRepositoryInterface $themeRepository
     * @param ThemeInterfaceFactory $themeFactory
     * @param ThemeJsonValidatorInterface $themeJsonValidator
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly ThemeRepositoryInterface $themeRepository,
        private readonly ThemeInterfaceFactory $themeFactory,
        private readonly ThemeJsonValidatorInterface $themeJsonValidator
    ) {
        parent::__construct($context);
    }

    /**
     * Import a theme from an uploaded JSON file
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $files = $this->getRequest()->getFiles('import_file');

            if (!$files || !isset($files['tmp_name']) || !$files['tmp_name']) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('No file was uploaded.'),
                ]);
            }

            $content = file_get_contents($files['tmp_name']);

            if ($content === false) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Failed to read uploaded file.'),
                ]);
            }

            $importData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Invalid JSON file: %1', json_last_error_msg()),
                ]);
            }

            $themeJson = is_string($content) ? $content : '';

            if (!$this->themeJsonValidator->validate($themeJson)) {
                $errors = $this->themeJsonValidator->getErrors();

                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Invalid theme structure: %1', implode(', ', $errors)),
                ]);
            }

            $themeName = $importData['name'] ?? 'Imported Theme';
            $storeId = (int)$this->getRequest()->getParam('store_id', 0);

            $theme = $this->themeFactory->create();
            $theme->setName((string)$themeName);
            $theme->setThemeJson($themeJson);
            $theme->setStoreId($storeId);
            $this->themeRepository->save($theme);

            return $resultJson->setData([
                'success' => true,
                'theme_id' => $theme->getThemeId(),
                'message' => (string)__('Theme "%1" imported successfully.', $themeName),
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
