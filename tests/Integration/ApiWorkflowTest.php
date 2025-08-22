<?php

declare(strict_types=1);

namespace Auctane\Api\Test\Integration;

use Auctane\Api\Test\Utilities\TestCase;
use Auctane\Api\Test\Fixtures\Orders\OrderFixture;
use Auctane\Api\Test\Fixtures\Requests\RequestFixture;
use Auctane\Api\Test\Fixtures\Config\ConfigFixture;
use Auctane\Api\Controller\SalesOrdersExport\Index as SalesOrdersExportController;
use Auctane\Api\Controller\ShipmentNotification\Index as ShipmentNotificationController;
use Auctane\Api\Controller\InventoryFetch\Index as InventoryFetchController;
use Auctane\Api\Controller\InventoryPush\Index as InventoryPushController;
use Auctane\Api\Model\Authorization;

/**
 * Integration tests for complete API workflows
 * 
 * Tests end-to-end scenarios combining multiple components
 * Requirements: 3.1, 3.2, 3.3
 */
class ApiWorkflowTest extends TestCase
{
    private SalesOrdersExportController $salesOrdersExportController;
    private ShipmentNotificationController $shipmentNotificationController;
    private InventoryFetchController $inventoryFetchController;
    private InventoryPushController $inventoryPushController;
    private Authorization $authorization;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create controllers with mocked dependencies
        $this->salesOrdersExportController = new SalesOrdersExportController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->mockFactory->createAuthorizationMock(),
            $this->mockFactory->createExportActionMock()
        );
        
        $this->shipmentNotificationController = new ShipmentNotificationController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->mockFactory->createAuthorizationMock(),
            $this->mockFactory->createShipNotifyActionMock()
        );
        
        $this->inventoryFetchController = new InventoryFetchController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->mockFactory->createAuthorizationMock(),
            $this->mockFactory->createInventoryFetchActionMock()
        );
        
        $this->inventoryPushController = new InventoryPushController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->mockFactory->createAuthorizationMock(),
            $this->mockFactory->createInventoryPushActionMock()
        );
        
        $this->authorization = new Authorization(
            $this->mockFactory->createScopeConfigMock(),
            $this->mockFactory->createStoreManagerMock()
        );
    }

    /**
     * Test complete order export workflow
     * 
     * @test
     */
    public function testCompleteOrderExportWorkflow(): void
    {
        // Arrange: Set up order data and authentication
        $orderData = OrderFixture::createSampleOrder();
        $apiKey = 'test-api-key-123';
        
        $request = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'page' => '1'
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        // Mock successful authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Act: Execute the complete workflow
        $result = $this->salesOrdersExportController->execute();
        
        // Assert: Verify successful response
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        
        // Verify the response contains expected order data structure
        $responseData = $result->getData();
        $this->assertArrayHasKey('orders', $responseData);
        $this->assertArrayHasKey('page', $responseData);
        $this->assertArrayHasKey('pages', $responseData);
    }

    /**
     * Test complete shipment notification workflow
     * 
     * @test
     */
    public function testCompleteShipmentNotificationWorkflow(): void
    {
        // Arrange: Set up shipment notification data
        $shipmentData = RequestFixture::createShipmentNotificationRequest();
        $apiKey = 'test-api-key-456';
        
        $request = $this->mockFactory->createHttpRequestMock();
        $request->method('getContent')->willReturn(json_encode($shipmentData));
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        // Mock successful authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Act: Execute the shipment notification workflow
        $result = $this->shipmentNotificationController->execute();
        
        // Assert: Verify successful processing
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        
        $responseData = $result->getData();
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
    }

    /**
     * Test complete inventory synchronization workflow
     * 
     * @test
     */
    public function testCompleteInventorySynchronizationWorkflow(): void
    {
        // Arrange: Set up inventory data
        $inventoryFetchData = RequestFixture::createInventoryFetchRequest();
        $inventoryPushData = RequestFixture::createInventoryPushRequest();
        $apiKey = 'test-api-key-789';
        
        // Mock successful authorization for both operations
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Step 1: Fetch current inventory
        $fetchRequest = $this->mockFactory->createHttpRequestMock();
        $fetchRequest->method('getContent')->willReturn(json_encode($inventoryFetchData));
        $fetchRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        $fetchResult = $this->inventoryFetchController->execute();
        
        // Step 2: Push updated inventory
        $pushRequest = $this->mockFactory->createHttpRequestMock();
        $pushRequest->method('getContent')->willReturn(json_encode($inventoryPushData));
        $pushRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        $pushResult = $this->inventoryPushController->execute();
        
        // Assert: Verify both operations completed successfully
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $fetchResult);
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $pushResult);
        
        $fetchResponseData = $fetchResult->getData();
        $pushResponseData = $pushResult->getData();
        
        $this->assertArrayHasKey('products', $fetchResponseData);
        $this->assertArrayHasKey('success', $pushResponseData);
        $this->assertTrue($pushResponseData['success']);
    }

    /**
     * Test error handling across workflow components
     * 
     * @test
     */
    public function testWorkflowErrorHandling(): void
    {
        // Arrange: Set up invalid request data
        $invalidApiKey = 'invalid-key';
        
        $request = $this->mockFactory->createHttpRequestMock([
            'action' => 'export'
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$invalidApiKey}");
        
        // Mock failed authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => 'valid-key'
        ]);
        
        // Act & Assert: Verify proper error handling
        $result = $this->salesOrdersExportController->execute();
        
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        $responseData = $result->getData();
        
        $this->assertArrayHasKey('error', $responseData);
        $this->assertStringContains('Unauthorized', $responseData['error']);
    }

    /**
     * Test workflow with complex order data
     * 
     * @test
     */
    public function testWorkflowWithComplexOrderData(): void
    {
        // Arrange: Create complex order with multiple items and custom fields
        $complexOrder = OrderFixture::createOrderWithCustomFields();
        $apiKey = 'test-complex-key';
        
        $request = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'order_number' => $complexOrder['increment_id']
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        // Mock successful authorization and complex order data
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Act: Execute export with complex data
        $result = $this->salesOrdersExportController->execute();
        
        // Assert: Verify complex data is handled correctly
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        
        $responseData = $result->getData();
        $this->assertArrayHasKey('orders', $responseData);
        
        if (!empty($responseData['orders'])) {
            $order = $responseData['orders'][0];
            $this->assertArrayHasKey('order_number', $order);
            $this->assertArrayHasKey('items', $order);
            $this->assertArrayHasKey('custom_field1', $order);
        }
    }
}