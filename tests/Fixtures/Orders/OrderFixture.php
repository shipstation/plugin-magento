<?php

namespace Tests\Fixtures\Orders;

use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderStatus;

/**
 * Fixture class for generating sample order data for testing
 */
class OrderFixture
{
    /**
     * Create a basic sample order with minimal required fields
     *
     * @return array
     */
    public static function createSampleOrder(): array
    {
        return [
            'order_id' => 'ORD-12345',
            'order_number' => '100000001',
            'status' => SalesOrderStatus::AWAITING_SHIPMENT,
            'paid_date' => '2024-01-15T10:30:00.000Z',
            'currency' => 'USD',
            'buyer' => [
                'buyer_id' => 'BUYER-001',
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+1-555-123-4567'
            ],
            'bill_to' => [
                'name' => 'John Doe',
                'company' => 'Acme Corp',
                'phone' => '+1-555-123-4567',
                'address_line_1' => '123 Main Street',
                'address_line_2' => 'Suite 100',
                'city' => 'New York',
                'state_province' => 'NY',
                'postal_code' => '10001',
                'country_code' => 'US',
                'residential_indicator' => 'Yes'
            ],
            'ship_from' => [
                'name' => 'Warehouse Team',
                'company' => 'Test Store',
                'address_line_1' => '456 Warehouse Ave',
                'city' => 'Los Angeles',
                'state_province' => 'CA',
                'postal_code' => '90210',
                'country_code' => 'US'
            ],
            'requested_fulfillments' => [
                [
                    'ship_to' => [
                        'name' => 'John Doe',
                        'address_line_1' => '789 Customer St',
                        'city' => 'Chicago',
                        'state_province' => 'IL',
                        'postal_code' => '60601',
                        'country_code' => 'US'
                    ],
                    'items' => [
                        [
                            'line_item_id' => 'ITEM-001',
                            'description' => 'Test Product',
                            'quantity' => 2,
                            'unit_price' => 29.99,
                            'product' => [
                                'product_id' => 'PROD-123',
                                'name' => 'Test Product',
                                'sku' => 'TEST-SKU-001'
                            ]
                        ]
                    ]
                ]
            ],
            'payment' => [
                'status' => 'Paid',
                'amount' => 59.98
            ],
            'created_date_time' => '2024-01-15T09:00:00.000Z',
            'modified_date_time' => '2024-01-15T10:30:00.000Z'
        ];
    }

    /**
     * Create an order with multiple items
     *
     * @param int $itemCount Number of items to include
     * @return array
     */
    public static function createOrderWithItems(int $itemCount): array
    {
        $order = self::createSampleOrder();
        $items = [];

        for ($i = 1; $i <= $itemCount; $i++) {
            $items[] = [
                'line_item_id' => "ITEM-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                'description' => "Test Product {$i}",
                'quantity' => rand(1, 5),
                'unit_price' => round(rand(1000, 9999) / 100, 2),
                'product' => [
                    'product_id' => "PROD-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'name' => "Test Product {$i}",
                    'sku' => "TEST-SKU-" . str_pad($i, 3, '0', STR_PAD_LEFT),
                    'weight' => [
                        'value' => round(rand(100, 2000) / 100, 2),
                        'unit' => 'lb'
                    ]
                ]
            ];
        }

        $order['requested_fulfillments'][0]['items'] = $items;
        
        // Update payment amount based on items
        $totalAmount = array_sum(array_map(
            fn($item) => $item['quantity'] * $item['unit_price'],
            $items
        ));
        $order['payment']['amount'] = round($totalAmount, 2);

        return $order;
    }

    /**
     * Create an order with custom fields and notes
     *
     * @return array
     */
    public static function createOrderWithCustomFields(): array
    {
        $order = self::createSampleOrder();
        
        $order['notes'] = [
            [
                'type' => 'CustomerNote',
                'text' => 'Please handle with care - fragile items',
                'created_date_time' => '2024-01-15T09:15:00.000Z'
            ],
            [
                'type' => 'InternalNote',
                'text' => 'Rush order - expedite processing',
                'created_date_time' => '2024-01-15T09:30:00.000Z'
            ]
        ];

        $order['integration_context'] = json_encode([
            'magento_store_id' => 1,
            'customer_group_id' => 2,
            'custom_attribute_1' => 'value1',
            'custom_attribute_2' => 'value2'
        ]);

        $order['order_url'] = 'https://store.example.com/admin/sales/order/view/order_id/12345/';

        return $order;
    }

    /**
     * Create an order with international shipping
     *
     * @return array
     */
    public static function createInternationalOrder(): array
    {
        $order = self::createSampleOrder();
        
        // Update shipping address to international
        $order['requested_fulfillments'][0]['ship_to'] = [
            'name' => 'Jean Dupont',
            'company' => 'Société Exemple',
            'address_line_1' => '123 Rue de la Paix',
            'city' => 'Paris',
            'state_province' => 'Île-de-France',
            'postal_code' => '75001',
            'country_code' => 'FR',
            'phone' => '+33-1-23-45-67-89'
        ];

        $order['currency'] = 'EUR';
        $order['payment']['amount'] = 49.99;

        // Add tax identifier for international order
        $order['tax_identifier'] = [
            'type' => 'VAT',
            'value' => 'FR12345678901'
        ];

        return $order;
    }

    /**
     * Create an order with different status
     *
     * @param string $status Order status
     * @return array
     */
    public static function createOrderWithStatus(string $status): array
    {
        $order = self::createSampleOrder();
        $order['status'] = $status;

        // Adjust dates based on status
        switch ($status) {
            case SalesOrderStatus::SHIPPED:
                $order['fulfilled_date'] = '2024-01-16T14:30:00.000Z';
                break;
            case SalesOrderStatus::CANCELLED:
                $order['paid_date'] = null;
                $order['payment']['status'] = 'Cancelled';
                break;
            case SalesOrderStatus::ON_HOLD:
                $order['paid_date'] = null;
                $order['payment']['status'] = 'Pending';
                break;
        }

        return $order;
    }

    /**
     * Create a minimal order with only required fields
     *
     * @return array
     */
    public static function createMinimalOrder(): array
    {
        return [
            'order_id' => 'MIN-001',
            'status' => SalesOrderStatus::AWAITING_SHIPMENT,
            'buyer' => [
                'name' => 'Test Buyer'
            ],
            'bill_to' => [
                'name' => 'Test Buyer',
                'address_line_1' => '123 Test St',
                'city' => 'Test City',
                'country_code' => 'US'
            ],
            'ship_from' => [
                'name' => 'Test Store',
                'address_line_1' => '456 Store Ave',
                'city' => 'Store City',
                'country_code' => 'US'
            ],
            'requested_fulfillments' => [
                [
                    'ship_to' => [
                        'name' => 'Test Buyer',
                        'address_line_1' => '789 Ship St',
                        'city' => 'Ship City',
                        'country_code' => 'US'
                    ],
                    'items' => [
                        [
                            'description' => 'Test Item',
                            'quantity' => 1
                        ]
                    ]
                ]
            ]
        ];
    }
}