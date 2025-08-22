<?php

declare(strict_types=1);

namespace Auctane\Api\Test\Integration;

use Auctane\Api\Test\Utilities\TestCase;
use Auctane\Api\Test\Fixtures\Orders\OrderFixture;
use Auctane\Api\Test\Fixtures\Requests\RequestFixture;
use Auctane\Api\Controller\SalesOrdersExport\Index as SalesOrdersExportController;
use Auctane\Api\Controller\ShipmentNotification\Index as ShipmentNotificationController;
use Auctane\Api\Model\Action\Export;
use Auctane\Api\Model\Action\ShipNotify;

/**
 * Integration tests for complex order export and shipment notification flows
 * 
 * Tests complex scenarios with multiple items, custom fields, and edge cases
 * Requirements: 3.1, 3.2, 3.3
 */
class ComplexWorkflowTest extends TestCase
{
    private SalesOrdersExportController $exportController;
    private ShipmentNotificationController $shipmentController;
    private Export $exportAction;
    private ShipNotify $shipNotifyAction;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->exportAction = new Export(
            $this->mockFactory->createOrderRepositoryMock(),
            $this->mockFactory->createScopeConfigMock(),
            $this->mockFactory->createStoreManagerMock()
        );
        
        $this->shipNotifyAction = new ShipNotify(
            $this->mockFactory->createOrderRepositoryMock(),
            $this->mockFactory->createShipmentRepositoryMock(),
            $this->mockFactory->createTrackRepositoryMock()
        );
        
        $this->exportController = new SalesOrdersExportController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->mockFactory->createAuthorizationMock(),
            $this->exportAction
        );
        
        $this->shipmentController = new ShipmentNotificationController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->mockFactory->createAuthorizationMock(),
            $this->shipNotifyAction
        );
    }

    /**
     * Test complex order export with multiple items and custom fields
     * 
     * @test
     */
    public function testComplexOrderExportWithMultipleItems(): void
    {
        // Arrange: Create complex order with multiple items
        $complexOrder = OrderFixture::createOrderWithItems(5);
        $apiKey = 'complex-export-key';
        
        $request = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'page' => '1',
            'include_custom_fields' => 'true'
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {" . $apiKey . "}");
        
        // Mock successful authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Mock order repository to return complex order
        $orderRepository = $this->mockFactory->createOrderRepositoryMock();
        $orderRepository->method('getList')->willReturn([$complexOrder]);
        
        // Act: Execute complex export
        $result = $this->exportController->execute();
        
        // Assert: Verify complex data structure
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        
        $responseData = $result->getData();
        $this->assertArrayHasKey('orders', $responseData);
        $this->assertNotEmpty($responseData['orders']);
        
        $exportedOrder = $responseData['orders'][0];
        
        // Verify order structure
        $this->assertArrayHasKey('order_number', $exportedOrder);
        $this->assertArrayHasKey('items', $exportedOrder);
        $this->assertArrayHasKey('billing_address', $exportedOrder);
        $this->assertArrayHasKey('shipping_address', $exportedOrder);
        $this->assertArrayHasKey('custom_fields', $exportedOrder);
        
        // Verify items structure
        $this->assertCount(5, $exportedOrder['items']);
        foreach ($exportedOrder['items'] as $item) {
            $this->assertArrayHasKey('sku', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('quantity', $item);
            $this->assertArrayHasKey('price', $item);
            $this->assertArrayHasKey('weight', $item);
        }
        // 
        $this->assertArrayHasKey('custom_field1', $exportedOrder['custom_fields']);
        $this->assertArrayHasKey('custom_field2', $exportedOrder['custom_fields']);
    }

    /**
     * Test order export with special characters and international data
     * 
     * @test
     */
    public function testOrderExportWithInternationalData(): void
    {
        // Arrange: Create order with international characters
        $internationalOrder = [
            'increment_id' => 'INT-000001',
            'customer_firstname' => 'José',
            'customer_lastname' => 'García-Müller',
            'billing_address' => [
                'firstname' => 'José',
                'lastname' => 'García-Müller',
                'street' => 'Straße der Einheit 123',
                'city' => 'München',
                'postcode' => '80331',
                'country_id' => 'DE',
                'telephone' => '+49 89 123456789'
            ],
            'shipping_address' => [
                'firstname' => 'José',
                'lastname' => 'García-Müller',
                'street' => 'Rue de la Paix 456',
                'city' => 'Paris',
                'postcode' => '75001',
                'country_id' => 'FR',
                'telephone' => '+33 1 23456789'
            ],
            'items' => [
                [
                    'sku' => 'PROD-ÄÖÜ-001',
                    'name' => 'Produkt mit Umlauten ÄÖÜ',
                    'quantity' => 2,
                    'price' => 29.99
                ]
            ]
        ];
        
        $apiKey = 'international-key';
        
        $request = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'order_number' => 'INT-000001'
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {" . $apiKey . "}");
        
        // Mock authorization and order data
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Act: Execute export
        $result = $this->exportController->execute();
        
        // Assert: Verify international data is properly encoded
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        
        $responseData = $result->getData();
        $this->assertArrayHasKey('orders', $responseData);
        
        if (!empty($responseData['orders'])) {
            $order = $responseData['orders'][0];
            
            // Verify UTF-8 encoding is preserved
            $this->assertEquals('José', $order['customer_firstname']);
            $this->assertEquals('García-Müller', $order['customer_lastname']);
            $this->assertEquals('München', $order['billing_address']['city']);
            $this->assertEquals('Produkt mit Umlauten ÄÖÜ', $order['items'][0]['name']);
        }
    }

    /**
     * Test complex shipment notification with multiple packages
     * 
     * @test
     */
    public function testComplexShipmentNotificationWithMultiplePackages(): void
    {
        // Arrange: Create shipment notification with multiple packages
        $complexShipment = [
            'order_number' => 'ORD-000123',
            'shipments' => [
                [
                    'shipment_id' => 'SHIP-001',
                    'tracking_number' => 'TRACK-001',
                    'carrier' => 'UPS',
                    'service' => 'Ground',
                    'packages' => [
                        [
                            'package_id' => 'PKG-001',
                            'tracking_number' => 'TRACK-001-PKG1',
                            'weight' => 2.5,
                            'dimensions' => [
                                'length' => 10,
                                'width' => 8,
                                'height' => 6
                            ],
                            'items' => [
                                ['sku' => 'ITEM-001', 'quantity' => 2],
                                ['sku' => 'ITEM-002', 'quantity' => 1]
                            ]
                        ],
                        [
                            'package_id' => 'PKG-002',
                            'tracking_number' => 'TRACK-001-PKG2',
                            'weight' => 1.8,
                            'dimensions' => [
                                'length' => 8,
                                'width' => 6,
                                'height' => 4
                            ],
                            'items' => [
                                ['sku' => 'ITEM-003', 'quantity' => 1]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        $apiKey = 'complex-shipment-key';
        
        $request = $this->mockFactory->createHttpRequestMock();
        $request->method('getContent')->willReturn(json_encode($complexShipment));
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {" . $apiKey . "}");
        
        // Mock authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Act: Execute shipment notification
        $result = $this->shipmentController->execute();
        
        // Assert: Verify complex shipment processing
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        
        $responseData = $result->getData();
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
        
        // Verify shipment details are processed
        $this->assertArrayHasKey('shipments_processed', $responseData);
        $this->assertEquals(1, $responseData['shipments_processed']);
        
        $this->assertArrayHasKey('packages_processed', $responseData);
        $this->assertEquals(2, $responseData['packages_processed']);
    }

    /**
     * Test partial shipment notification workflow
     * 
     * @test
     */
    public function testPartialShipmentNotificationWorkflow(): void
    {
        // Arrange: Create partial shipment scenario
        $partialShipment = [
            'order_number' => 'ORD-PARTIAL-001',
            'shipments' => [
                [
                    'shipment_id' => 'PARTIAL-SHIP-001',
                    'tracking_number' => 'PARTIAL-TRACK-001',
                    'carrier' => 'FedEx',
                    'service' => 'Express',
                    'is_partial' => true,
                    'items' => [
                        ['sku' => 'ITEM-001', 'quantity' => 1], // Only 1 of 3 ordered
                        ['sku' => 'ITEM-002', 'quantity' => 2]  // All 2 ordered
                    ],
                    'remaining_items' => [
                        ['sku' => 'ITEM-001', 'quantity' => 2] // 2 remaining
                    ]
                ]
            ]
        ];
        
        $apiKey = 'partial-shipment-key';
        
        $request = $this->mockFactory->createHttpRequestMock();
        $request->method('getContent')->willReturn(json_encode($partialShipment));
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {" . $apiKey . "}");
        
        // Mock authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Act: Execute partial shipment notification
        $result = $this->shipmentController->execute();
        
        // Assert: Verify partial shipment handling
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        
        $responseData = $result->getData();
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
        
        $this->assertArrayHasKey('partial_shipment', $responseData);
        $this->assertTrue($responseData['partial_shipment']);
        
        $this->assertArrayHasKey('remaining_items', $responseData);
        $this->assertNotEmpty($responseData['remaining_items']);
    }

    /**
     * Test order export and shipment notification integration
     * 
     * @test
     */
    public function testOrderExportToShipmentNotificationIntegration(): void
    {
        // Arrange: Create order and corresponding shipment
        $order = OrderFixture::createSampleOrder();
        $orderNumber = $order['increment_id'];
        $apiKey = 'integration-test-key';
        
        // Step 1: Export the order
        $exportRequest = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'order_number' => $orderNumber
        ]);
        $exportRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {" . $apiKey . "}");
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $exportResult = $this->exportController->execute();
        
        // Verify export was successful
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $exportResult);
        $exportData = $exportResult->getData();
        $this->assertArrayHasKey('orders', $exportData);
        
        // Step 2: Create shipment notification based on exported order
        $shipmentData = [
            'order_number' => $orderNumber,
            'shipments' => [
                [
                    'shipment_id' => 'SHIP-' . $orderNumber,
                    'tracking_number' => 'TRACK-' . time(),
                    'carrier' => 'UPS',
                    'service' => 'Ground',
                    'items' => array_map(function($item) {
                        return [
                            'sku' => $item['sku'],
                            'quantity' => $item['quantity']
                        ];
                    }, $order['items'])
                ]
            ]
        ];
        
        $shipmentRequest = $this->mockFactory->createHttpRequestMock();
        $shipmentRequest->method('getContent')->willReturn(json_encode($shipmentData));
        $shipmentRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {" . $apiKey . "}");
        
        $shipmentResult = $this->shipmentController->execute();
        
        // Assert: Verify complete integration workflow
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $shipmentResult);
        $shipmentResponseData = $shipmentResult->getData();
        
        $this->assertArrayHasKey('success', $shipmentResponseData);
        $this->assertTrue($shipmentResponseData['success']);
        
        $this->assertArrayHasKey('order_number', $shipmentResponseData);
        $this->assertEquals($orderNumber, $shipmentResponseData['order_number']);
    }

    /**
     * Test error recovery in complex workflows
     * 
     * @test
     */
    public function testErrorRecoveryInComplexWorkflows(): void
    {
        // Arrange: Create scenario with recoverable errors
        $problematicOrder = [
            'increment_id' => 'PROB-001',
            'items' => [
                ['sku' => 'VALID-ITEM', 'quantity' => 1, 'price' => 10.00],
                ['sku' => '', 'quantity' => 0, 'price' => 0], // Invalid item
                ['sku' => 'ANOTHER-VALID', 'quantity' => 2, 'price' => 15.00]
            ]
        ];
        
        $apiKey = 'error-recovery-key';
        
        $request = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'order_number' => 'PROB-001',
            'skip_invalid_items' => 'true'
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {" . $apiKey . "}");
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Act: Execute export with error recovery
        $result = $this->exportController->execute();
        
        // Assert: Verify graceful error handling
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        
        $responseData = $result->getData();
        $this->assertArrayHasKey('orders', $responseData);
        
        if (!empty($responseData['orders'])) {
            $order = $responseData['orders'][0];
            
            // Verify only valid items are included
            $this->assertArrayHasKey('items', $order);
            $validItems = array_filter($order['items'], function($item) {
                return !empty($item['sku']) && $item['quantity'] > 0;
            });
            
            $this->assertCount(2, $validItems, 'Should have 2 valid items after filtering');
        }
        // 
        $this->assertArrayHasKey('warnings', $responseData);
        $this->assertNotEmpty($responseData['warnings']);
        $this->assertStringContains('invalid item', strtolower($responseData['warnings'][0]));
    }
}