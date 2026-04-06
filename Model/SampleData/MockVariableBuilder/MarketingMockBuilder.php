<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\SampleData\MockVariableBuilder;

use Hryvinskyi\EmailTemplateEditor\Api\MockVariableBuilderInterface;
use Magento\Framework\DataObject;

class MarketingMockBuilder implements MockVariableBuilderInterface
{
    /**
     * @inheritDoc
     */
    public function build(string $templateIdentifier, int $storeId): array
    {
        if (str_starts_with($templateIdentifier, 'newsletter_subscription')) {
            return $this->getNewsletterVars();
        }

        if (str_starts_with($templateIdentifier, 'contact_email')) {
            return $this->getContactVars();
        }

        if (str_starts_with($templateIdentifier, 'catalog_productalert_email_stock')) {
            return $this->getProductAlertStockVars();
        }

        if (str_starts_with($templateIdentifier, 'catalog_productalert_email_price')) {
            return $this->getProductAlertPriceVars();
        }

        if (str_starts_with($templateIdentifier, 'wishlist_email')) {
            return $this->getWishlistVars();
        }

        if (str_starts_with($templateIdentifier, 'sendfriend_email')) {
            return $this->getSendFriendVars();
        }

        return $this->getNewsletterVars();
    }

    /**
     * @return array<string, mixed>
     */
    private function getNewsletterVars(): array
    {
        return [
            'subscriber_data' => new DataObject([
                'confirmation_link' => 'https://example.com/newsletter/subscriber/confirm/?id=1&code=abc123',
                'email' => 'john.doe@example.com',
                'name' => 'John Doe',
            ]),
            'customer_name' => 'John Doe',
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getContactVars(): array
    {
        return [
            'data' => new DataObject([
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'telephone' => '+1 (555) 987-6543',
                'comment' => 'Hello, I have a question about my recent order. Could you please help me with the shipping details? Thank you!',
            ]),
            'customer_name' => 'Jane Smith',
            'customer' => new DataObject([
                'name' => 'Jane Smith',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
                'email' => 'jane.smith@example.com',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getProductAlertStockVars(): array
    {
        return [
            'customerName' => 'John Doe',
            'alertGrid' => '<table style="width:100%;border-collapse:collapse;">'
                . '<tr><td style="padding:10px;border-bottom:1px solid #e3e3e3;">'
                . '<strong>Sample Product</strong><br/>'
                . '<span style="color:#666;">SKU: SAMPLE-001</span><br/>'
                . '<span style="color:#79a22e;font-weight:bold;">Now In Stock</span>'
                . '</td></tr>'
                . '<tr><td style="padding:10px;border-bottom:1px solid #e3e3e3;">'
                . '<strong>Another Product</strong><br/>'
                . '<span style="color:#666;">SKU: SAMPLE-002</span><br/>'
                . '<span style="color:#79a22e;font-weight:bold;">Now In Stock</span>'
                . '</td></tr></table>',
            'customer_name' => 'John Doe',
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getProductAlertPriceVars(): array
    {
        return [
            'customerName' => 'John Doe',
            'alertGrid' => '<table style="width:100%;border-collapse:collapse;">'
                . '<tr><td style="padding:10px;border-bottom:1px solid #e3e3e3;">'
                . '<strong>Sample Product</strong><br/>'
                . '<span style="color:#666;">SKU: SAMPLE-001</span><br/>'
                . '<span style="text-decoration:line-through;color:#666;">$149.99</span> '
                . '<span style="color:#eb5202;font-weight:bold;">$99.99</span>'
                . '</td></tr>'
                . '<tr><td style="padding:10px;border-bottom:1px solid #e3e3e3;">'
                . '<strong>Another Product</strong><br/>'
                . '<span style="color:#666;">SKU: SAMPLE-002</span><br/>'
                . '<span style="text-decoration:line-through;color:#666;">$79.99</span> '
                . '<span style="color:#eb5202;font-weight:bold;">$59.99</span>'
                . '</td></tr></table>',
            'customer_name' => 'John Doe',
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getWishlistVars(): array
    {
        return [
            'customerName' => 'John Doe',
            'items' => '<table style="width:100%;border-collapse:collapse;">'
                . '<tr><td style="padding:10px;border-bottom:1px solid #e3e3e3;">'
                . '<strong>Wishlist Product 1</strong> - $49.99'
                . '</td></tr>'
                . '<tr><td style="padding:10px;border-bottom:1px solid #e3e3e3;">'
                . '<strong>Wishlist Product 2</strong> - $89.99'
                . '</td></tr></table>',
            'message' => 'Check out these products from my wishlist!',
            'viewOnSiteLink' => 'https://example.com/wishlist/shared/index/code/abc123/',
            'customer_name' => 'John Doe',
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getSendFriendVars(): array
    {
        return [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'recipients' => 'jane.smith@example.com',
            'product' => new DataObject([
                'name' => 'Amazing Sample Product',
                'url' => 'https://example.com/amazing-sample-product.html',
                'price' => '$99.99',
            ]),
            'message' => 'Hey, I found this great product and thought you might like it!',
            'sender_name' => 'John Doe',
            'sender_email' => 'john.doe@example.com',
            'product_name' => 'Amazing Sample Product',
            'product_url' => 'https://example.com/amazing-sample-product.html',
            'customer_name' => 'John Doe',
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
            ]),
        ];
    }
}
