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

class OrderMockBuilder implements MockVariableBuilderInterface
{
    /**
     * @inheritDoc
     */
    public function build(string $templateIdentifier, int $storeId): array
    {
        if (str_starts_with($templateIdentifier, 'sales_email_order_comment')
            || str_starts_with($templateIdentifier, 'sales_email_order_guest_comment')
        ) {
            return array_merge($this->getOrderVars(), $this->getCommentVars());
        }

        if (str_starts_with($templateIdentifier, 'sales_email_invoice_comment')) {
            return array_merge($this->getOrderVars(), $this->getInvoiceVars(), $this->getCommentVars());
        }

        if (str_starts_with($templateIdentifier, 'sales_email_invoice')) {
            return array_merge($this->getOrderVars(), $this->getInvoiceVars());
        }

        if (str_starts_with($templateIdentifier, 'sales_email_shipment_comment')) {
            return array_merge($this->getOrderVars(), $this->getShipmentVars(), $this->getCommentVars());
        }

        if (str_starts_with($templateIdentifier, 'sales_email_shipment')) {
            return array_merge($this->getOrderVars(), $this->getShipmentVars());
        }

        if (str_starts_with($templateIdentifier, 'sales_email_creditmemo_comment')) {
            return array_merge($this->getOrderVars(), $this->getCreditmemoVars(), $this->getCommentVars());
        }

        if (str_starts_with($templateIdentifier, 'sales_email_creditmemo')) {
            return array_merge($this->getOrderVars(), $this->getCreditmemoVars());
        }

        if (str_starts_with($templateIdentifier, 'checkout_payment_failed')) {
            return $this->getCheckoutPaymentFailedVars();
        }

        if (str_starts_with($templateIdentifier, 'sales_email_order_ready')) {
            return array_merge($this->getOrderVars(), $this->getInStorePickupVars());
        }

        if (str_starts_with($templateIdentifier, 'sales_cancellation')) {
            return $this->getOrderCancellationVars();
        }

        return $this->getOrderVars();
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrderVars(): array
    {
        $billingAddress = new DataObject([
            'firstname' => 'John',
            'lastname' => 'Doe',
            'street' => '123 Main Street',
            'city' => 'Springfield',
            'region' => 'IL',
            'postcode' => '62701',
            'country_id' => 'US',
            'telephone' => '(555) 123-4567',
        ]);

        return [
            'order' => new DataObject([
                'increment_id' => '100000001',
                'created_at' => date('M d, Y'),
                'customer_name' => 'John Doe',
                'customer_firstname' => 'John',
                'customer_lastname' => 'Doe',
                'customer_email' => 'john.doe@example.com',
                'shipping_description' => 'Flat Rate - Fixed',
                'is_not_virtual' => true,
                'billing_address' => $billingAddress,
                'shipping_address' => $billingAddress,
                'store_id' => 1,
                'status_label' => 'Pending',
                'grand_total' => 129.99,
                'subtotal' => 109.99,
                'shipping_amount' => 10.00,
                'tax_amount' => 10.00,
            ]),
            'order_id' => '100000001',
            'order_data' => [
                'customer_name' => 'John Doe',
                'is_not_virtual' => true,
                'email_customer_note' => '',
                'frontend_status_label' => 'Pending',
            ],
            'billing' => $billingAddress,
            'payment_html' => 'Check / Money order',
            'formattedShippingAddress' => 'John Doe<br/>123 Main Street<br/>Springfield, IL 62701<br/>US<br/>T: (555) 123-4567',
            'formattedBillingAddress' => 'John Doe<br/>123 Main Street<br/>Springfield, IL 62701<br/>US<br/>T: (555) 123-4567',
            'shipping_msg' => '',
            'order_area' => 'frontend',
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
    private function getCommentVars(): array
    {
        return [
            'comment' => 'Your order has been updated. Please review the details below.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getInvoiceVars(): array
    {
        return [
            'invoice' => new DataObject([
                'increment_id' => '100000001',
                'created_at' => date('M d, Y'),
                'grand_total' => 129.99,
                'subtotal' => 109.99,
                'shipping_amount' => 10.00,
                'tax_amount' => 10.00,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getShipmentVars(): array
    {
        return [
            'shipment' => new DataObject([
                'increment_id' => '100000001',
                'created_at' => date('M d, Y'),
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCreditmemoVars(): array
    {
        return [
            'creditmemo' => new DataObject([
                'increment_id' => '100000001',
                'created_at' => date('M d, Y'),
                'grand_total' => 129.99,
                'subtotal' => 109.99,
                'shipping_amount' => 10.00,
                'tax_amount' => 10.00,
                'adjustment_positive' => 0.00,
                'adjustment_negative' => 0.00,
            ]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getCheckoutPaymentFailedVars(): array
    {
        return [
            'reason' => 'The credit card number is invalid. Please verify and try again.',
            'checkoutType' => 'onepage',
            'customer' => new DataObject([
                'name' => 'John Doe',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'email' => 'john.doe@example.com',
            ]),
            'customer_name' => 'John Doe',
            'customerEmail' => 'john.doe@example.com',
            'items' => 'Sample Product x 1 — $99.99<br/>Another Product x 2 — $49.99',
            'total' => '$199.97',
            'billingAddressHtml' => 'John Doe<br/>123 Main Street<br/>Springfield, IL 62701<br/>US',
            'shippingAddressHtml' => 'John Doe<br/>123 Main Street<br/>Springfield, IL 62701<br/>US',
            'paymentMethod' => 'Credit Card',
            'dateAndTime' => date('M d, Y H:i:s'),
            'shippingMethod' => 'Flat Rate - Fixed',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getInStorePickupVars(): array
    {
        return [
            'order_data' => [
                'customer_name' => 'John Doe',
                'is_not_virtual' => true,
                'email_customer_note' => '',
                'frontend_status_label' => 'Ready for Pickup',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getOrderCancellationVars(): array
    {
        return [
            'order' => new DataObject([
                'increment_id' => '100000001',
                'created_at' => date('M d, Y'),
                'customer_name' => 'John Doe',
                'grand_total' => 129.99,
            ]),
            'order_id' => '100000001',
            'confirmation_link' => 'https://example.com/sales/order/cancel/confirm/?id=1&token=abc123',
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
