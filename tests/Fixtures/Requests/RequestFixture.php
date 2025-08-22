<?php

namespace Tests\Fixtures\Requests;

/**
 * Fixture class for generating sample API request and response data for testing
 */
class RequestFixture
{
    /**
     * Create a sample sales orders export request
     *
     * @return array
     */
    public static function createSalesOrdersExportRequest(): array
    {
        return [
            'criteria' => [
                'start_date' => '2024-01-01T00:00:00.000Z',
                'end_date' => '2024-01-31T23:59:59.999Z',
                'status_filter' => ['awaiting_shipment', 'shipped'],
                'store_id' => 1
            ],
            'cursor' => null,
            'sales_order_field_mappings' => [
                'order_number_field' => 'increment_id',
                'customer_notes_field' => 'customer_note',
                'gift_message_field' => 'gift_message'
            ],
            'sales_order_status_mappings' => [
                'pending' => 'awaiting_payment',
                'processing' => 'awaiting_shipment',
                'complete' => 'shipped',
                'canceled' => 'cancelled'
            ]
        ];
    }

    /**
     * Create a sample inventory fetch request
     *
     * @return array
     */
    public static function createInventoryFetchRequest(): array
    {
        return [
            'criteria' => [
                'sku_list' => ['TEST-SKU-001', 'TEST-SKU-002', 'TEST-SKU-003'],
                'modified_since' => '2024-01-01T00:00:00.000Z',
                'store_id' => 1
            ],
            'cursor' => null
        ];
    }

    /**
     * Create a sample inventory push request
     *
     * @return array
     */
    public static function createInventoryPushRequest(): array
    {
        return [
            'inventory_items' => [
                [
                    'sku' => 'TEST-SKU-001',
                    'quantity' => 100,
                    'location_id' => 'WAREHOUSE-001'
                ],
                [
                    'sku' => 'TEST-SKU-002',
                    'quantity' => 50,
                    'location_id' => 'WAREHOUSE-001'
                ]
            ]
        ];
    }

    /**
     * Create a sample shipment notification request
     *
     * @return array
     */
    public static function createShipmentNotificationRequest(): array
    {
        return [
            'order_id' => 'ORD-12345',
            'tracking_number' => '1Z999AA1234567890',
            'carrier_code' => 'ups',
            'service_code' => 'ups_ground',
            'ship_date' => '2024-01-16T10:00:00.000Z',
            'items' => [
                [
                    'line_item_id' => 'ITEM-001',
                    'quantity_shipped' => 2
                ]
            ],
            'tracking_url' => 'https://www.ups.com/track?tracknum=1Z999AA1234567890'
        ];
    }

    /**
     * Create a sample sales orders export response
     *
     * @return array
     */
    public static function createSalesOrdersExportResponse(): array
    {
        return [
            'sales_orders' => [
                [
                    'order_id' => 'ORD-12345',
                    'order_number' => '100000001',
                    'status' => 'awaiting_shipment',
                    'paid_date' => '2024-01-15T10:30:00.000Z',
                    'currency' => 'USD',
                    'buyer' => [
                        'buyer_id' => 'BUYER-001',
                        'name' => 'John Doe',
                        'email' => 'john.doe@example.com'
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
                                    'unit_price' => 29.99
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'cursor' => 'eyJvcmRlcl9pZCI6Ik9SRC0xMjM0NSJ9'
        ];
    }

    /**
     * Create a sample inventory fetch response
     *
     * @return array
     */
    public static function createInventoryFetchResponse(): array
    {
        return [
            'inventory_items' => [
                [
                    'sku' => 'TEST-SKU-001',
                    'quantity' => 100,
                    'location_id' => 'WAREHOUSE-001',
                    'modified_date_time' => '2024-01-15T12:00:00.000Z'
                ],
                [
                    'sku' => 'TEST-SKU-002',
                    'quantity' => 50,
                    'location_id' => 'WAREHOUSE-001',
                    'modified_date_time' => '2024-01-15T12:00:00.000Z'
                ]
            ],
            'cursor' => null
        ];
    }

    /**
     * Create a sample inventory push response
     *
     * @return array
     */
    public static function createInventoryPushResponse(): array
    {
        return [
            'results' => [
                [
                    'sku' => 'TEST-SKU-001',
                    'status' => 'success',
                    'quantity_updated' => 100
                ],
                [
                    'sku' => 'TEST-SKU-002',
                    'status' => 'success',
                    'quantity_updated' => 50
                ]
            ]
        ];
    }

    /**
     * Create a sample shipment notification response
     *
     * @return array
     */
    public static function createShipmentNotificationResponse(): array
    {
        return [
            'order_id' => 'ORD-12345',
            'status' => 'success',
            'message' => 'Shipment notification processed successfully',
            'tracking_number' => '1Z999AA1234567890'
        ];
    }

    /**
     * Create an error response sample
     *
     * @param int $statusCode HTTP status code
     * @param string $message Error message
     * @return array
     */
    public static function createErrorResponse(int $statusCode = 400, string $message = 'Bad Request'): array
    {
        return [
            'error' => [
                'code' => $statusCode,
                'message' => $message,
                'details' => [
                    'timestamp' => '2024-01-15T10:30:00.000Z',
                    'request_id' => 'req_' . uniqid()
                ]
            ]
        ];
    }

    /**
     * Create authentication request data
     *
     * @param string $apiKey
     * @return array
     */
    public static function createAuthRequest(string $apiKey = 'test-api-key-12345'): array
    {
        return [
            'SS-UserName' => $apiKey,
            'SS-Password' => $apiKey,
            'action' => 'export',
            'format' => 'json'
        ];
    }

    /**
     * Create HTTP headers for API requests
     *
     * @param string $apiKey
     * @return array
     */
    public static function createApiHeaders(string $apiKey = 'test-api-key-12345'): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
            'User-Agent' => 'Auctane-Api/2.5.7'
        ];
    }

    /**
     * Create a paginated request with cursor
     *
     * @param string $cursor
     * @return array
     */
    public static function createPaginatedRequest(string $cursor): array
    {
        $request = self::createSalesOrdersExportRequest();
        $request['cursor'] = $cursor;
        return $request;
    }

    /**
     * Create a request with validation errors
     *
     * @return array
     */
    public static function createInvalidRequest(): array
    {
        return [
            'criteria' => [
                'start_date' => 'invalid-date',
                'end_date' => null,
                'status_filter' => 'invalid-status'
            ],
            'cursor' => 123, // Should be string or null
            'sales_order_field_mappings' => 'invalid-mapping' // Should be object
        ];
    }
}