<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Controller\Adminhtml\Template;

use Hryvinskyi\EmailTemplateEditor\Api\SampleDataProviderPoolInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateRendererInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class SendTestEmail extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Hryvinskyi_EmailTemplateEditor::editor';

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param TemplateRendererInterface $templateRenderer
     * @param SampleDataProviderPoolInterface $sampleDataProviderPool
     * @param EmailMessageInterfaceFactory $emailMessageFactory
     * @param MimeMessageInterfaceFactory $mimeMessageFactory
     * @param MimePartInterfaceFactory $mimePartFactory
     * @param AddressConverter $addressConverter
     * @param TransportInterfaceFactory $transportFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
        private readonly TemplateRendererInterface $templateRenderer,
        private readonly SampleDataProviderPoolInterface $sampleDataProviderPool,
        private readonly EmailMessageInterfaceFactory $emailMessageFactory,
        private readonly MimeMessageInterfaceFactory $mimeMessageFactory,
        private readonly MimePartInterfaceFactory $mimePartFactory,
        private readonly AddressConverter $addressConverter,
        private readonly TransportInterfaceFactory $transportFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Send a test email with the current template content
     *
     * @return Json
     */
    public function execute(): Json
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $recipientEmail = trim((string)$this->getRequest()->getParam('recipient_email', ''));
            $templateContent = (string)$this->getRequest()->getParam('template_content', '');
            $templateSubject = (string)$this->getRequest()->getParam('template_subject', '');
            $templateIdentifier = (string)$this->getRequest()->getParam('template_identifier', '');
            $storeId = (int)$this->getRequest()->getParam('store_id', 0);
            $customCss = $this->getRequest()->getParam('custom_css');
            $tailwindCss = $this->getRequest()->getParam('tailwind_css');
            $providerCode = (string)$this->getRequest()->getParam('provider_code', 'mock');
            $entityId = $this->getRequest()->getParam('entity_id');

            if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Please enter a valid email address.'),
                ]);
            }

            if ($templateContent === '') {
                return $resultJson->setData([
                    'success' => false,
                    'message' => (string)__('Template content is required.'),
                ]);
            }

            $variables = [];
            $provider = $this->sampleDataProviderPool->getProvider($providerCode);
            $entityIdValue = $entityId !== null && $entityId !== '' ? (string)$entityId : null;
            $variables = $provider->getVariables($templateIdentifier, $storeId, $entityIdValue);

            $html = $this->templateRenderer->render(
                $templateContent,
                $variables,
                $storeId,
                $customCss !== null && $customCss !== '' ? (string)$customCss : null,
                $tailwindCss !== null && $tailwindCss !== '' ? (string)$tailwindCss : null,
                $templateIdentifier !== '' ? $templateIdentifier : null
            );

            $processedSubject = $templateSubject !== ''
                ? $this->templateRenderer->renderPlain($templateSubject, $variables, $storeId, $templateIdentifier)
                : '';
            $subject = '[TEST] ' . ($processedSubject !== '' ? $processedSubject : 'Email Template Preview');

            $mimePart = $this->mimePartFactory->create([
                'content' => $html,
                'type' => 'text/html',
                'charset' => 'UTF-8',
            ]);

            $mimeMessage = $this->mimeMessageFactory->create([
                'parts' => [$mimePart],
            ]);

            $to = $this->addressConverter->convert($recipientEmail);

            $senderEmail = $this->scopeConfig->getValue(
                'trans_email/ident_general/email',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ?: 'no-reply@example.com';
            $senderName = $this->scopeConfig->getValue(
                'trans_email/ident_general/name',
                ScopeInterface::SCOPE_STORE,
                $storeId
            ) ?: 'Store';
            $from = $this->addressConverter->convert($senderEmail, $senderName);

            $emailMessage = $this->emailMessageFactory->create([
                'body' => $mimeMessage,
                'to' => [$to],
                'from' => [$from],
                'subject' => $subject,
            ]);

            $transport = $this->transportFactory->create([
                'message' => $emailMessage,
            ]);

            $transport->sendMessage();

            return $resultJson->setData([
                'success' => true,
                'message' => (string)__('Test email sent to %1', $recipientEmail),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SendTestEmail failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return $resultJson->setData([
                'success' => false,
                'message' => (string)__('Failed to send test email: %1', $e->getMessage()),
            ]);
        }
    }
}
