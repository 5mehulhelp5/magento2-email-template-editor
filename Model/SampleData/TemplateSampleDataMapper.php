<?php
/**
 * Copyright (c) 2026. Volodymyr Hryvinskyi. All rights reserved.
 * Author: Volodymyr Hryvinskyi <volodymyr@hryvinskyi.com>
 * GitHub: https://github.com/hryvinskyi
 */

declare(strict_types=1);

namespace Hryvinskyi\EmailTemplateEditor\Model\SampleData;

use Hryvinskyi\EmailTemplateEditor\Api\TemplateSampleDataMapperInterface;

class TemplateSampleDataMapper implements TemplateSampleDataMapperInterface
{
    /**
     * Template identifier prefix to category mapping
     *
     * @var array<string, string>
     */
    private const CATEGORY_MAP = [
        'sales_email_order_comment' => 'order_comment',
        'sales_email_order' => 'order',
        'sales_email_invoice_comment' => 'invoice_comment',
        'sales_email_invoice' => 'invoice',
        'sales_email_shipment_comment' => 'shipment_comment',
        'sales_email_shipment' => 'shipment',
        'sales_email_creditmemo_comment' => 'creditmemo_comment',
        'sales_email_creditmemo' => 'creditmemo',
        'sales_email_order_ready' => 'in_store_pickup',
        'sales_cancellation' => 'order_cancellation',
        'customer_create_account' => 'customer_new_account',
        'customer_password' => 'customer_password',
        'customer_account_information' => 'customer_account_change',
        'newsletter_subscription' => 'newsletter',
        'contact_email' => 'contact',
        'catalog_productalert_email_stock' => 'product_alert_stock',
        'catalog_productalert_email_price' => 'product_alert_price',
        'wishlist_email' => 'wishlist',
        'sendfriend_email' => 'send_friend',
        'checkout_payment_failed' => 'checkout_payment_failed',
        'admin_emails' => 'admin_user',
        'admin_adobe_ims_email' => 'header_footer',
        'design_email_header' => 'header_footer',
        'design_email_footer' => 'header_footer',
    ];

    /**
     * @inheritDoc
     */
    public function getCategory(string $templateIdentifier): string
    {
        foreach (self::CATEGORY_MAP as $prefix => $category) {
            if (str_starts_with($templateIdentifier, $prefix)) {
                return $category;
            }
        }

        return 'generic';
    }
}
