<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\SampleData;

use Hryvinskyi\EmailTemplateEditor\Api\MockVariableBuilderPoolInterface;
use Hryvinskyi\EmailTemplateEditor\Api\SampleDataProviderInterface;
use Hryvinskyi\EmailTemplateEditor\Api\TemplateSampleDataMapperInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Psr\Log\LoggerInterface;

class LastOrderProvider implements SampleDataProviderInterface
{
    /**
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreManagerInterface $storeManager
     * @param AddressRenderer $addressRenderer
     * @param PaymentHelper $paymentHelper
     * @param TemplateSampleDataMapperInterface $templateMapper
     * @param MockVariableBuilderPoolInterface $builderPool
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly OrderCollectionFactory $orderCollectionFactory,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly AddressRenderer $addressRenderer,
        private readonly PaymentHelper $paymentHelper,
        private readonly TemplateSampleDataMapperInterface $templateMapper,
        private readonly MockVariableBuilderPoolInterface $builderPool,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Last Order';
    }

    /**
     * @inheritDoc
     */
    public function getCode(): string
    {
        return 'last_order';
    }

    /**
     * @inheritDoc
     */
    public function supportsEntitySearch(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function searchEntities(string $query, int $storeId, int $limit = 10): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getVariables(string $templateIdentifier, int $storeId, ?string $entityId = null): array
    {
        $category = $this->templateMapper->getCategory($templateIdentifier);

        try {
            $collection = $this->orderCollectionFactory->create();
            $collection->addFieldToFilter('store_id', $storeId ?: ['gt' => 0]);
            $collection->setOrder('entity_id', 'DESC');
            $collection->setPageSize(1);

            /** @var Order|null $order */
            $order = $collection->getFirstItem();

            if (!$order || !$order->getId()) {
                return $this->builderPool->getBuilder($category)->build($templateIdentifier, $storeId);
            }

            $order = $this->orderRepository->get((int)$order->getId());
            $store = $this->storeManager->getStore($order->getStoreId());

            $formattedShippingAddress = '';
            if ($order->getShippingAddress()) {
                $formattedShippingAddress = $this->addressRenderer->format($order->getShippingAddress(), 'html');
            }

            $formattedBillingAddress = '';
            if ($order->getBillingAddress()) {
                $formattedBillingAddress = $this->addressRenderer->format($order->getBillingAddress(), 'html');
            }

            $paymentHtml = '';
            try {
                $paymentHtml = $this->paymentHelper->getInfoBlockHtml(
                    $order->getPayment(),
                    $order->getStoreId()
                );
            } catch (\Exception) {
                $paymentHtml = $order->getPayment() ? $order->getPayment()->getMethod() : '';
            }

            $variables = [
                'order' => $order,
                'order_id' => $order->getIncrementId(),
                'order_data' => [
                    'customer_name' => $order->getCustomerName(),
                    'is_not_virtual' => !$order->getIsVirtual(),
                    'email_customer_note' => $order->getEmailCustomerNote() ?? '',
                    'frontend_status_label' => $order->getFrontendStatusLabel(),
                ],
                'billing' => $order->getBillingAddress(),
                'payment_html' => $paymentHtml,
                'store' => $store,
                'store_name' => $store->getName(),
                'store_url' => $store->getBaseUrl(),
                'formattedShippingAddress' => $formattedShippingAddress,
                'formattedBillingAddress' => $formattedBillingAddress,
                'order_area' => 'frontend',
                'customer_name' => $order->getCustomerName(),
                'customer' => new DataObject([
                    'name' => $order->getCustomerName(),
                    'firstname' => $order->getCustomerFirstname(),
                    'lastname' => $order->getCustomerLastname(),
                    'email' => $order->getCustomerEmail(),
                ]),
            ];

            // Merge template-specific extras (comment, invoice, shipment, creditmemo, etc.)
            $categoryExtras = $this->getCategoryExtras($category);
            $variables = array_merge($variables, $categoryExtras);

            return $variables;
        } catch (\Exception $e) {
            $this->logger->error('Failed to load last order sample data: ' . $e->getMessage());
            return $this->builderPool->getBuilder($category)->build($templateIdentifier, $storeId);
        }
    }

    /**
     * Get additional variables specific to the template category
     *
     * @param string $category
     * @return array<string, mixed>
     */
    private function getCategoryExtras(string $category): array
    {
        return match ($category) {
            'order_comment', 'invoice_comment', 'shipment_comment', 'creditmemo_comment' => [
                'comment' => 'Your order has been updated. Please review the details below.',
            ],
            'invoice', 'invoice_comment' => [
                'invoice' => new DataObject([
                    'increment_id' => '100000001',
                    'created_at' => date('M d, Y'),
                ]),
                'comment' => $category === 'invoice_comment'
                    ? 'Your order has been updated. Please review the details below.'
                    : null,
            ],
            'shipment', 'shipment_comment' => [
                'shipment' => new DataObject([
                    'increment_id' => '100000001',
                    'created_at' => date('M d, Y'),
                ]),
                'comment' => $category === 'shipment_comment'
                    ? 'Your order has been updated. Please review the details below.'
                    : null,
            ],
            'creditmemo', 'creditmemo_comment' => [
                'creditmemo' => new DataObject([
                    'increment_id' => '100000001',
                    'created_at' => date('M d, Y'),
                ]),
                'comment' => $category === 'creditmemo_comment'
                    ? 'Your order has been updated. Please review the details below.'
                    : null,
            ],
            'in_store_pickup' => [
                'order_data' => [
                    'customer_name' => 'John Doe',
                    'is_not_virtual' => true,
                    'email_customer_note' => '',
                    'frontend_status_label' => 'Ready for Pickup',
                ],
            ],
            default => [],
        };
    }
}
