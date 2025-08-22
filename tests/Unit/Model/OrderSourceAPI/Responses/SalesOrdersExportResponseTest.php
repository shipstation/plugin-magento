<?php

namespace Auctane\Api\Tests\Unit\Model\OrderSourceAPI\Responses;

use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrder;
use Auctane\Api\Model\OrderSourceAPI\Responses\SalesOrdersExportResponse;
use Auctane\Api\Tests\Utilities\TestCase;

class SalesOrdersExportResponseTest extends TestCase
{
    /**
     * Test SalesOrdersExportResponse construction and property initialization
     */
    public function testConstruction(): void
    {
        $response = new SalesOrdersExportResponse();
        
        // Test that properties exist and can be accessed
        $this->assertTrue(property_exists($response, 'sales_orders'));
        $this->assertTrue(property_exists($response, 'cursor'));
    }

    /**
     * Test SalesOrdersExportResponse with empty sales orders array
     */
    public function testWithEmptySalesOrders(): void
    {
        $response = new SalesOrdersExportResponse();
        $response->sales_orders = [];
        $response->cursor = null;
        
        $this->assertEquals([], $response->sales_orders);
        $this->assertNull($response->cursor);
    }

    /**
     * Test SalesOrdersExportResponse with sales orders data
     */
    public function testWithSalesOrdersData(): void
    {
        $response = new SalesOrdersExportResponse();
        
        // Create mock sales orders
        $salesOrder1 = $this->createMock(SalesOrder::class);
        $salesOrder2 = $this->createMock(SalesOrder::class);
        
        $response->sales_orders = [$salesOrder1, $salesOrder2];
        $response->cursor = 'next_page_cursor_123';
        
        $this->assertCount(2, $response->sales_orders);
        $this->assertInstanceOf(SalesOrder::class, $response->sales_orders[0]);
        $this->assertInstanceOf(SalesOrder::class, $response->sales_orders[1]);
        $this->assertEquals('next_page_cursor_123', $response->cursor);
    }

    /**
     * Test SalesOrdersExportResponse with cursor for pagination
     */
    public function testWithPaginationCursor(): void
    {
        $response = new SalesOrdersExportResponse();
        $response->sales_orders = [];
        $response->cursor = 'eyJwYWdlIjoyLCJsaXN0IjoxMDB9';
        
        $this->assertEquals([], $response->sales_orders);
        $this->assertEquals('eyJwYWdlIjoyLCJsaXN0IjoxMDB9', $response->cursor);
    }

    /**
     * Test SalesOrdersExportResponse with null cursor (last page)
     */
    public function testWithNullCursorLastPage(): void
    {
        $response = new SalesOrdersExportResponse();
        
        $salesOrder = $this->createMock(SalesOrder::class);
        $response->sales_orders = [$salesOrder];
        $response->cursor = null; // Indicates last page
        
        $this->assertCount(1, $response->sales_orders);
        $this->assertNull($response->cursor);
    }

    /**
     * Test SalesOrdersExportResponse with large number of sales orders
     */
    public function testWithLargeNumberOfSalesOrders(): void
    {
        $response = new SalesOrdersExportResponse();
        
        $salesOrders = [];
        for ($i = 0; $i < 100; $i++) {
            $salesOrders[] = $this->createMock(SalesOrder::class);
        }
        
        $response->sales_orders = $salesOrders;
        $response->cursor = 'large_batch_cursor';
        
        $this->assertCount(100, $response->sales_orders);
        $this->assertEquals('large_batch_cursor', $response->cursor);
        
        // Verify all items are SalesOrder instances
        foreach ($response->sales_orders as $salesOrder) {
            $this->assertInstanceOf(SalesOrder::class, $salesOrder);
        }
    }

    /**
     * Test SalesOrdersExportResponse property types
     */
    public function testPropertyTypes(): void
    {
        $response = new SalesOrdersExportResponse();
        
        // Test that sales_orders can be set as array
        $response->sales_orders = [];
        $this->assertIsArray($response->sales_orders);
        
        // Test that cursor can be set as string
        $response->cursor = 'test_cursor';
        $this->assertIsString($response->cursor);
        
        // Test that cursor can be set as null
        $response->cursor = null;
        $this->assertNull($response->cursor);
    }

    /**
     * Test SalesOrdersExportResponse with mixed data scenarios
     */
    public function testWithMixedDataScenarios(): void
    {
        $response = new SalesOrdersExportResponse();
        
        // Scenario 1: First page with data and cursor
        $salesOrder1 = $this->createMock(SalesOrder::class);
        $response->sales_orders = [$salesOrder1];
        $response->cursor = 'first_page_cursor';
        
        $this->assertCount(1, $response->sales_orders);
        $this->assertEquals('first_page_cursor', $response->cursor);
        
        // Scenario 2: Middle page with data and cursor
        $salesOrder2 = $this->createMock(SalesOrder::class);
        $salesOrder3 = $this->createMock(SalesOrder::class);
        $response->sales_orders = [$salesOrder2, $salesOrder3];
        $response->cursor = 'middle_page_cursor';
        
        $this->assertCount(2, $response->sales_orders);
        $this->assertEquals('middle_page_cursor', $response->cursor);
        
        // Scenario 3: Last page with data but no cursor
        $salesOrder4 = $this->createMock(SalesOrder::class);
        $response->sales_orders = [$salesOrder4];
        $response->cursor = null;
        
        $this->assertCount(1, $response->sales_orders);
        $this->assertNull($response->cursor);
    }

    /**
     * Test SalesOrdersExportResponse cursor string formats
     */
    public function testCursorStringFormats(): void
    {
        $response = new SalesOrdersExportResponse();
        $response->sales_orders = [];
        
        // Test various cursor formats
        $cursorFormats = [
            'simple_string',
            'base64_encoded_cursor',
            'eyJwYWdlIjoxLCJsaW1pdCI6NTB9', // Base64 encoded JSON
            'timestamp_1234567890',
            'uuid_550e8400-e29b-41d4-a716-446655440000',
            ''
        ];
        
        foreach ($cursorFormats as $cursor) {
            $response->cursor = $cursor;
            $this->assertEquals($cursor, $response->cursor);
        }
    }
}