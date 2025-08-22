<?php

namespace Auctane\Api\Tests\Unit\Model\OrderSourceAPI\Models;

use Auctane\Api\Model\OrderSourceAPI\Models\Address;
use Auctane\Api\Model\OrderSourceAPI\Models\BillTo;
use Auctane\Api\Model\OrderSourceAPI\Models\Buyer;
use Auctane\Api\Model\OrderSourceAPI\Models\OriginalOrderSource;
use Auctane\Api\Model\OrderSourceAPI\Models\Payment;
use Auctane\Api\Model\OrderSourceAPI\Models\RequestedFulfillment;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrder;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderStatus;
use Auctane\Api\Model\OrderSourceAPI\Models\TaxIdentifier;
use Auctane\Api\Tests\Utilities\TestCase;

class SalesOrderTest extends TestCase
{
    /**
     * Test SalesOrder construction with null data
     */
    public function testConstructionWithNullData(): void
    {
        $salesOrder = new SalesOrder(null);
        
        $this->assertNull($salesOrder->order_number);
        $this->assertNull($salesOrder->paid_date);
        $this->assertNull($salesOrder->fulfilled_date);
        $this->assertNull($salesOrder->original_order_source);
        $this->assertEquals([], $salesOrder->requested_fulfillments);
        $this->assertNull($salesOrder->buyer);
        $this->assertNull($salesOrder->bill_to);
        $this->assertNull($salesOrder->currency);
        $this->assertNull($salesOrder->tax_identifier);
        $this->assertNull($salesOrder->payment);
        $this->assertNull($salesOrder->order_url);
        $this->assertNull($salesOrder->notes);
        $this->assertNull($salesOrder->integration_context);
        $this->assertNull($salesOrder->created_date_time);
        $this->assertNull($salesOrder->modified_date_time);
        $this->assertNull($salesOrder->fulfillment_channel);
    }

    /**
     * Test SalesOrder construction with minimal required data
     */
    public function testConstructionWithMinimalData(): void
    {
        $data = [
            'order_id' => 'ORDER123',
            'status' => SalesOrderStatus::AwaitingShipment,
            'buyer' => [
                'buyer_id' => 'BUYER123',
                'name' => 'John Doe',
                'email' => 'john@example.com'
            ],
            'bill_to' => [
                'name' => 'John Doe',
                'address_line_1' => '123 Main St',
                'city' => 'New York',
                'state_province' => 'NY',
                'postal_code' => '10001',
                'country_code' => 'US'
            ],
            'ship_from' => [
                'name' => 'Warehouse',
                'address_line_1' => '456 Warehouse Ave',
                'city' => 'Los Angeles',
                'state_province' => 'CA',
                'postal_code' => '90210',
                'country_code' => 'US'
            ]
        ];
        
        $salesOrder = new SalesOrder($data);
        
        $this->assertEquals('ORDER123', $salesOrder->order_id);
        $this->assertEquals(SalesOrderStatus::AwaitingShipment, $salesOrder->status);
        $this->assertInstanceOf(Buyer::class, $salesOrder->buyer);
        $this->assertInstanceOf(BillTo::class, $salesOrder->bill_to);
        $this->assertInstanceOf(Address::class, $salesOrder->ship_from);
    }

    /**
     * Test SalesOrder construction with complete data
     */
    public function testConstructionWithCompleteData(): void
    {
        $data = [
            'order_id' => 'ORDER456',
            'order_number' => 'ORD-2023-456',
            'status' => SalesOrderStatus::Completed,
            'paid_date' => '2023-06-15T10:30:00Z',
            'fulfilled_date' => '2023-06-16T14:45:00Z',
            'original_order_source' => [
                'source_id' => 'AMAZON123',
                'source_name' => 'Amazon'
            ],
            'requested_fulfillments' => [
                [
                    'fulfillment_id' => 'FULFILL123',
                    'ship_to' => [
                        'name' => 'Jane Smith',
                        'address_line_1' => '789 Oak St',
                        'city' => 'Chicago',
                        'state_province' => 'IL',
                        'postal_code' => '60601',
                        'country_code' => 'US'
                    ]
                ]
            ],
            'buyer' => [
                'buyer_id' => 'BUYER456',
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'phone' => '+1-555-123-4567'
            ],
            'bill_to' => [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'address_line_1' => '789 Oak St',
                'city' => 'Chicago',
                'state_province' => 'IL',
                'postal_code' => '60601',
                'country_code' => 'US'
            ],
            'currency' => 'USD',
            'tax_identifier' => [
                'type' => 'VAT',
                'value' => 'GB123456789'
            ],
            'payment' => [
                'method' => 'credit_card',
                'status' => 'paid'
            ],
            'ship_from' => [
                'name' => 'Main Warehouse',
                'address_line_1' => '100 Industrial Blvd',
                'city' => 'Dallas',
                'state_province' => 'TX',
                'postal_code' => '75201',
                'country_code' => 'US'
            ],
            'order_url' => 'https://store.example.com/orders/456',
            'notes' => [
                'Customer requested expedited shipping',
                'Gift wrap requested'
            ],
            'integration_context' => '{"marketplace_order_id": "AMZ-456-789"}',
            'created_date_time' => '2023-06-15T08:00:00Z',
            'modified_date_time' => '2023-06-16T15:00:00Z',
            'fulfillment_channel' => 'SellerFulfilled'
        ];
        
        $salesOrder = new SalesOrder($data);
        
        $this->assertEquals('ORDER456', $salesOrder->order_id);
        $this->assertEquals('ORD-2023-456', $salesOrder->order_number);
        $this->assertEquals(SalesOrderStatus::Completed, $salesOrder->status);
        $this->assertEquals('2023-06-15T10:30:00Z', $salesOrder->paid_date);
        $this->assertEquals('2023-06-16T14:45:00Z', $salesOrder->fulfilled_date);
        $this->assertInstanceOf(OriginalOrderSource::class, $salesOrder->original_order_source);
        $this->assertCount(1, $salesOrder->requested_fulfillments);
        $this->assertInstanceOf(RequestedFulfillment::class, $salesOrder->requested_fulfillments[0]);
        $this->assertInstanceOf(Buyer::class, $salesOrder->buyer);
        $this->assertInstanceOf(BillTo::class, $salesOrder->bill_to);
        $this->assertEquals('USD', $salesOrder->currency);
        $this->assertInstanceOf(TaxIdentifier::class, $salesOrder->tax_identifier);
        $this->assertInstanceOf(Payment::class, $salesOrder->payment);
        $this->assertInstanceOf(Address::class, $salesOrder->ship_from);
        $this->assertEquals('https://store.example.com/orders/456', $salesOrder->order_url);
        $this->assertEquals(['Customer requested expedited shipping', 'Gift wrap requested'], $salesOrder->notes);
        $this->assertEquals('{"marketplace_order_id": "AMZ-456-789"}', $salesOrder->integration_context);
        $this->assertEquals('2023-06-15T08:00:00Z', $salesOrder->created_date_time);
        $this->assertEquals('2023-06-16T15:00:00Z', $salesOrder->modified_date_time);
        $this->assertEquals('SellerFulfilled', $salesOrder->fulfillment_channel);
    }

    /**
     * Test SalesOrder construction with multiple requested fulfillments
     */
    public function testConstructionWithMultipleRequestedFulfillments(): void
    {
        $data = [
            'order_id' => 'ORDER789',
            'status' => SalesOrderStatus::PendingFulfillment,
            'requested_fulfillments' => [
                [
                    'fulfillment_id' => 'FULFILL001',
                    'ship_to' => [
                        'name' => 'Customer 1',
                        'address_line_1' => '111 First St',
                        'city' => 'Boston',
                        'state_province' => 'MA',
                        'postal_code' => '02101',
                        'country_code' => 'US'
                    ]
                ],
                [
                    'fulfillment_id' => 'FULFILL002',
                    'ship_to' => [
                        'name' => 'Customer 2',
                        'address_line_1' => '222 Second St',
                        'city' => 'Seattle',
                        'state_province' => 'WA',
                        'postal_code' => '98101',
                        'country_code' => 'US'
                    ]
                ]
            ],
            'buyer' => [
                'name' => 'Multi Order Buyer'
            ],
            'bill_to' => [
                'name' => 'Multi Order Buyer'
            ],
            'ship_from' => [
                'name' => 'Distribution Center'
            ]
        ];
        
        $salesOrder = new SalesOrder($data);
        
        $this->assertEquals('ORDER789', $salesOrder->order_id);
        $this->assertEquals(SalesOrderStatus::PendingFulfillment, $salesOrder->status);
        $this->assertCount(2, $salesOrder->requested_fulfillments);
        $this->assertInstanceOf(RequestedFulfillment::class, $salesOrder->requested_fulfillments[0]);
        $this->assertInstanceOf(RequestedFulfillment::class, $salesOrder->requested_fulfillments[1]);
    }

    /**
     * Test SalesOrder construction with different order statuses
     */
    public function testConstructionWithDifferentOrderStatuses(): void
    {
        $statuses = [
            SalesOrderStatus::AwaitingPayment,
            SalesOrderStatus::AwaitingShipment,
            SalesOrderStatus::Cancelled,
            SalesOrderStatus::Completed,
            SalesOrderStatus::OnHold,
            SalesOrderStatus::PendingFulfillment
        ];
        
        foreach ($statuses as $status) {
            $data = [
                'order_id' => 'ORDER_' . $status->value,
                'status' => $status,
                'buyer' => ['name' => 'Test Buyer'],
                'bill_to' => ['name' => 'Test Buyer'],
                'ship_from' => ['name' => 'Test Warehouse']
            ];
            
            $salesOrder = new SalesOrder($data);
            
            $this->assertEquals($status, $salesOrder->status);
            $this->assertEquals('ORDER_' . $status->value, $salesOrder->order_id);
        }
    }

    /**
     * Test SalesOrder construction with empty requested fulfillments
     */
    public function testConstructionWithEmptyRequestedFulfillments(): void
    {
        $data = [
            'order_id' => 'ORDER_EMPTY',
            'status' => SalesOrderStatus::AwaitingShipment,
            'requested_fulfillments' => [],
            'buyer' => ['name' => 'Empty Fulfillment Buyer'],
            'bill_to' => ['name' => 'Empty Fulfillment Buyer'],
            'ship_from' => ['name' => 'Empty Fulfillment Warehouse']
        ];
        
        $salesOrder = new SalesOrder($data);
        
        $this->assertEquals('ORDER_EMPTY', $salesOrder->order_id);
        $this->assertEquals([], $salesOrder->requested_fulfillments);
    }

    /**
     * Test SalesOrder construction with international currency
     */
    public function testConstructionWithInternationalCurrency(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'CAD', 'AUD', 'JPY'];
        
        foreach ($currencies as $currency) {
            $data = [
                'order_id' => 'ORDER_' . $currency,
                'status' => SalesOrderStatus::AwaitingShipment,
                'currency' => $currency,
                'buyer' => ['name' => 'International Buyer'],
                'bill_to' => ['name' => 'International Buyer'],
                'ship_from' => ['name' => 'International Warehouse']
            ];
            
            $salesOrder = new SalesOrder($data);
            
            $this->assertEquals($currency, $salesOrder->currency);
        }
    }

    /**
     * Test SalesOrder construction with fulfillment channels
     */
    public function testConstructionWithFulfillmentChannels(): void
    {
        $channels = ['SellerFulfilled', 'MerchantFulfilled', 'AmazonFBA', 'ThirdParty'];
        
        foreach ($channels as $channel) {
            $data = [
                'order_id' => 'ORDER_' . $channel,
                'status' => SalesOrderStatus::AwaitingShipment,
                'fulfillment_channel' => $channel,
                'buyer' => ['name' => 'Channel Test Buyer'],
                'bill_to' => ['name' => 'Channel Test Buyer'],
                'ship_from' => ['name' => 'Channel Test Warehouse']
            ];
            
            $salesOrder = new SalesOrder($data);
            
            $this->assertEquals($channel, $salesOrder->fulfillment_channel);
        }
    }

    /**
     * Test SalesOrder construction with ISO 8601 date formats
     */
    public function testConstructionWithIso8601Dates(): void
    {
        $data = [
            'order_id' => 'ORDER_DATES',
            'status' => SalesOrderStatus::Completed,
            'paid_date' => '2023-12-25T15:30:45.123Z',
            'fulfilled_date' => '2023-12-26T09:15:30.456Z',
            'created_date_time' => '2023-12-24T12:00:00.000Z',
            'modified_date_time' => '2023-12-26T10:00:00.789Z',
            'buyer' => ['name' => 'Date Test Buyer'],
            'bill_to' => ['name' => 'Date Test Buyer'],
            'ship_from' => ['name' => 'Date Test Warehouse']
        ];
        
        $salesOrder = new SalesOrder($data);
        
        $this->assertEquals('2023-12-25T15:30:45.123Z', $salesOrder->paid_date);
        $this->assertEquals('2023-12-26T09:15:30.456Z', $salesOrder->fulfilled_date);
        $this->assertEquals('2023-12-24T12:00:00.000Z', $salesOrder->created_date_time);
        $this->assertEquals('2023-12-26T10:00:00.789Z', $salesOrder->modified_date_time);
    }
}