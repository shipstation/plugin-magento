<?php

namespace Auctane\Api\Tests\Unit\Model\OrderSourceAPI\Requests;

use Auctane\Api\Model\OrderSourceAPI\Models\Auth;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderCustomFieldMappings;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderCustomStatusMappings;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderExportCriteria;
use Auctane\Api\Model\OrderSourceAPI\Requests\SalesOrdersExportRequest;
use Auctane\Api\Tests\Utilities\TestCase;

class SalesOrdersExportRequestTest extends TestCase
{
    /**
     * Test SalesOrdersExportRequest construction with null data
     */
    public function testConstructionWithNullData(): void
    {
        $request = new SalesOrdersExportRequest(null);
        
        $this->assertNull($request->transaction_id);
        $this->assertNull($request->auth);
        $this->assertNull($request->criteria);
        $this->assertNull($request->cursor);
        $this->assertNull($request->sales_order_field_mappings);
        $this->assertNull($request->sales_order_status_mappings);
    }

    /**
     * Test SalesOrdersExportRequest construction with empty array
     */
    public function testConstructionWithEmptyArray(): void
    {
        $request = new SalesOrdersExportRequest([]);
        
        $this->assertNull($request->transaction_id);
        $this->assertInstanceOf(Auth::class, $request->auth);
        $this->assertNull($request->criteria);
        $this->assertNull($request->cursor);
        $this->assertNull($request->sales_order_field_mappings);
        $this->assertNull($request->sales_order_status_mappings);
    }

    /**
     * Test SalesOrdersExportRequest construction with minimal data
     */
    public function testConstructionWithMinimalData(): void
    {
        $data = [
            'transaction_id' => 'txn_123456',
            'auth' => [
                'api_key' => 'test_api_key_123'
            ]
        ];
        
        $request = new SalesOrdersExportRequest($data);
        
        $this->assertEquals('txn_123456', $request->transaction_id);
        $this->assertInstanceOf(Auth::class, $request->auth);
        $this->assertNull($request->criteria);
        $this->assertNull($request->cursor);
        $this->assertNull($request->sales_order_field_mappings);
        $this->assertNull($request->sales_order_status_mappings);
    }

    /**
     * Test SalesOrdersExportRequest construction with complete data
     */
    public function testConstructionWithCompleteData(): void
    {
        $data = [
            'transaction_id' => 'txn_789012',
            'auth' => [
                'api_key' => 'test_api_key_789'
            ],
            'criteria' => [
                'start_date' => '2023-01-01T00:00:00Z',
                'end_date' => '2023-12-31T23:59:59Z',
                'page_size' => 100
            ],
            'cursor' => 'cursor_abc123',
            'sales_order_field_mappings' => [
                'custom_field_1' => 'order_attribute_1',
                'custom_field_2' => 'order_attribute_2'
            ],
            'sales_order_status_mappings' => [
                'pending' => 'new',
                'processing' => 'processing',
                'shipped' => 'complete'
            ]
        ];
        
        $request = new SalesOrdersExportRequest($data);
        
        $this->assertEquals('txn_789012', $request->transaction_id);
        $this->assertInstanceOf(Auth::class, $request->auth);
        $this->assertInstanceOf(SalesOrderExportCriteria::class, $request->criteria);
        $this->assertEquals('cursor_abc123', $request->cursor);
        $this->assertInstanceOf(SalesOrderCustomFieldMappings::class, $request->sales_order_field_mappings);
        $this->assertInstanceOf(SalesOrderCustomStatusMappings::class, $request->sales_order_status_mappings);
    }

    /**
     * Test SalesOrdersExportRequest construction with criteria only
     */
    public function testConstructionWithCriteriaOnly(): void
    {
        $data = [
            'auth' => [
                'api_key' => 'test_key'
            ],
            'criteria' => [
                'start_date' => '2023-06-01T00:00:00Z',
                'end_date' => '2023-06-30T23:59:59Z',
                'store_ids' => [1, 2, 3],
                'order_statuses' => ['processing', 'shipped']
            ]
        ];
        
        $request = new SalesOrdersExportRequest($data);
        
        $this->assertInstanceOf(Auth::class, $request->auth);
        $this->assertInstanceOf(SalesOrderExportCriteria::class, $request->criteria);
        $this->assertNull($request->cursor);
        $this->assertNull($request->sales_order_field_mappings);
        $this->assertNull($request->sales_order_status_mappings);
    }

    /**
     * Test SalesOrdersExportRequest construction with cursor for pagination
     */
    public function testConstructionWithCursor(): void
    {
        $data = [
            'auth' => [
                'api_key' => 'pagination_key'
            ],
            'cursor' => 'eyJwYWdlIjoyLCJsaW1pdCI6NTB9'
        ];
        
        $request = new SalesOrdersExportRequest($data);
        
        $this->assertInstanceOf(Auth::class, $request->auth);
        $this->assertEquals('eyJwYWdlIjoyLCJsaW1pdCI6NTB9', $request->cursor);
        $this->assertNull($request->criteria);
        $this->assertNull($request->sales_order_field_mappings);
        $this->assertNull($request->sales_order_status_mappings);
    }

    /**
     * Test SalesOrdersExportRequest construction with field mappings
     */
    public function testConstructionWithFieldMappings(): void
    {
        $data = [
            'auth' => [
                'api_key' => 'mapping_key'
            ],
            'sales_order_field_mappings' => [
                'external_order_id' => 'increment_id',
                'external_customer_id' => 'customer_id',
                'external_status' => 'status'
            ]
        ];
        
        $request = new SalesOrdersExportRequest($data);
        
        $this->assertInstanceOf(Auth::class, $request->auth);
        $this->assertInstanceOf(SalesOrderCustomFieldMappings::class, $request->sales_order_field_mappings);
        $this->assertNull($request->criteria);
        $this->assertNull($request->cursor);
        $this->assertNull($request->sales_order_status_mappings);
    }

    /**
     * Test SalesOrdersExportRequest construction with status mappings
     */
    public function testConstructionWithStatusMappings(): void
    {
        $data = [
            'auth' => [
                'api_key' => 'status_key'
            ],
            'sales_order_status_mappings' => [
                'new' => 'pending',
                'processing' => 'processing',
                'complete' => 'shipped',
                'canceled' => 'canceled'
            ]
        ];
        
        $request = new SalesOrdersExportRequest($data);
        
        $this->assertInstanceOf(Auth::class, $request->auth);
        $this->assertInstanceOf(SalesOrderCustomStatusMappings::class, $request->sales_order_status_mappings);
        $this->assertNull($request->criteria);
        $this->assertNull($request->cursor);
        $this->assertNull($request->sales_order_field_mappings);
    }

    /**
     * Test SalesOrdersExportRequest inherits from RequestBase
     */
    public function testInheritsFromRequestBase(): void
    {
        $data = [
            'transaction_id' => 'inheritance_test',
            'auth' => [
                'api_key' => 'base_test_key'
            ]
        ];
        
        $request = new SalesOrdersExportRequest($data);
        
        // Test that base class properties are properly set
        $this->assertEquals('inheritance_test', $request->transaction_id);
        $this->assertInstanceOf(Auth::class, $request->auth);
    }

    /**
     * Test SalesOrdersExportRequest with all optional fields as null
     */
    public function testConstructionWithAllOptionalFieldsNull(): void
    {
        $data = [
            'auth' => [
                'api_key' => 'null_test_key'
            ],
            'criteria' => null,
            'cursor' => null,
            'sales_order_field_mappings' => null,
            'sales_order_status_mappings' => null
        ];
        
        $request = new SalesOrdersExportRequest($data);
        
        $this->assertInstanceOf(Auth::class, $request->auth);
        $this->assertNull($request->criteria);
        $this->assertNull($request->cursor);
        $this->assertNull($request->sales_order_field_mappings);
        $this->assertNull($request->sales_order_status_mappings);
    }
}