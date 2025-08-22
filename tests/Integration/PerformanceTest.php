<?php

declare(strict_types=1);

namespace Auctane\Api\Tests\Integration;

use Auctane\Api\Tests\Utilities\TestCase;
use Auctane\Api\Tests\Fixtures\Orders\OrderFixture;
use Auctane\Api\Controller\SalesOrdersExport\Index as SalesOrdersExportController;
use Auctane\Api\Controller\ShipmentNotification\Index as ShipmentNotificationController;

/**
 * Performance and reliability tests for API endpoints
 * 
 * Tests handling of large datasets, concurrent requests, and resource constraints
 * Requirements: 1.2, 2.4
 */
class PerformanceTest extends TestCase
{
    private SalesOrdersExportController $exportController;
    private ShipmentNotificationController $shipmentController;
    private float $maxExecutionTime = 2.0; // 2 seconds max
    private int $maxMemoryUsage = 50 * 1024 * 1024; // 50MB max

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->exportController = new SalesOrdersExportController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->mockFactory->createAuthorizationMock(),
            $this->mockFactory->createExportActionMock()
        );
        
        $this->shipmentController = new ShipmentNotificationController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->mockFactory->createAuthorizationMock(),
            $this->mockFactory->createShipNotifyActionMock()
        );
    }

    /**
     * Test performance with large dataset export
     * 
     * @test
     */
    public function testLargeDatasetExportPerformance(): void
    {
        // Arrange: Create large dataset simulation
        $largeOrderCount = 1000;
        $apiKey = 'performance-test-key';
        
        $request = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'page_size' => $largeOrderCount
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        // Mock large dataset
        $orders = [];
        for ($i = 1; $i <= $largeOrderCount; $i++) {
            $orders[] = OrderFixture::createSampleOrder();
        }
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Act & Assert: Measure performance
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $result = $this->exportController->execute();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Performance assertions
        $this->assertLessThan($this->maxExecutionTime, $executionTime, 
            "Export took {$executionTime}s, should be under {$this->maxExecutionTime}s");
        
        $this->assertLessThan($this->maxMemoryUsage, $memoryUsed,
            "Memory usage {$memoryUsed} bytes, should be under {$this->maxMemoryUsage} bytes");
        
        // Verify result is still valid
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
    }

    /**
     * Test concurrent request handling simulation
     * 
     * @test
     */
    public function testConcurrentRequestHandling(): void
    {
        // Arrange: Simulate multiple concurrent requests
        $concurrentRequests = 10;
        $apiKey = 'concurrent-test-key';
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $requests = [];
        for ($i = 1; $i <= $concurrentRequests; $i++) {
            $requests[] = $this->mockFactory->createHttpRequestMock([
                'action' => 'export',
                'order_number' => "ORD-{$i}",
                'request_id' => $i
            ]);
            $requests[$i-1]->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        }
        // 
        $startTime = microtime(true);
        $results = [];
        
        foreach ($requests as $request) {
            $results[] = $this->exportController->execute();
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Assert: Verify all requests completed successfully
        $this->assertCount($concurrentRequests, $results);
        
        foreach ($results as $result) {
            $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        }
        // 
        $averageTimePerRequest = $totalTime / $concurrentRequests;
        $this->assertLessThan(0.5, $averageTimePerRequest, 
            "Average time per request {$averageTimePerRequest}s should be under 0.5s");
    }

    /**
     * Test memory usage with multiple large shipment notifications
     * 
     * @test
     */
    public function testMemoryUsageWithLargeShipments(): void
    {
        // Arrange: Create large shipment notifications
        $largeShipmentCount = 100;
        $itemsPerShipment = 50;
        $apiKey = 'memory-test-key';
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $startMemory = memory_get_usage(true);
        
        // Act: Process multiple large shipments
        for ($i = 1; $i <= $largeShipmentCount; $i++) {
            $shipmentData = [
                'order_number' => "ORD-LARGE-{$i}",
                'shipments' => [
                    [
                        'shipment_id' => "SHIP-{$i}",
                        'tracking_number' => "TRACK-{$i}",
                        'carrier' => 'UPS',
                        'items' => []
                    ]
                ]
            ];
            
            // Add many items to simulate large shipment
            for ($j = 1; $j <= $itemsPerShipment; $j++) {
                $shipmentData['shipments'][0]['items'][] = [
                    'sku' => "ITEM-{$i}-{$j}",
                    'quantity' => rand(1, 10)
                ];
            }
            
            $request = $this->mockFactory->createHttpRequestMock();
            $request->method('getContent')->willReturn(json_encode($shipmentData));
            $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
            
            $result = $this->shipmentController->execute();
            
            // Verify each shipment processes successfully
            $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
            
            // Check memory usage periodically
            if ($i % 10 === 0) {
                $currentMemory = memory_get_usage(true);
                $memoryIncrease = $currentMemory - $startMemory;
                
                $this->assertLessThan($this->maxMemoryUsage, $memoryIncrease,
                    "Memory increase {$memoryIncrease} bytes after {$i} shipments should be under limit");
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $totalMemoryUsed = $finalMemory - $startMemory;
        
        // Assert: Total memory usage should be reasonable
        $this->assertLessThan($this->maxMemoryUsage, $totalMemoryUsed,
            "Total memory usage {$totalMemoryUsed} bytes should be under {$this->maxMemoryUsage} bytes");
    }

    /**
     * Test execution time constraints under load
     * 
     * @test
     */
    public function testExecutionTimeConstraints(): void
    {
        // Arrange: Create time-intensive scenario
        $complexOrderCount = 50;
        $apiKey = 'time-test-key';
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Create complex orders with many items and custom fields
        $complexOrders = [];
        for ($i = 1; $i <= $complexOrderCount; $i++) {
            $complexOrders[] = OrderFixture::createOrderWithItems(20); // 20 items each
        }
        
        $request = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'include_custom_fields' => 'true',
            'include_order_notes' => 'true',
            'detailed_items' => 'true'
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        // Act: Measure execution time
        $executionTimes = [];
        
        for ($i = 0; $i < 5; $i++) { // Run 5 times to get average
            $startTime = microtime(true);
            
            $result = $this->exportController->execute();
            
            $endTime = microtime(true);
            $executionTimes[] = $endTime - $startTime;
            
            // Verify result is valid
            $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
        }
        // 
        $averageTime = array_sum($executionTimes) / count($executionTimes);
        $maxTime = max($executionTimes);
        $minTime = min($executionTimes);
        
        $this->assertLessThan($this->maxExecutionTime, $averageTime,
            "Average execution time {$averageTime}s should be under {$this->maxExecutionTime}s");
        
        $this->assertLessThan($this->maxExecutionTime * 1.5, $maxTime,
            "Maximum execution time {$maxTime}s should be under " . ($this->maxExecutionTime * 1.5) . "s");
        
        // Check for consistent performance (max shouldn't be much higher than min)
        $timeVariance = $maxTime - $minTime;
        $this->assertLessThan($averageTime * 0.5, $timeVariance,
            "Time variance {$timeVariance}s should be less than 50% of average time");
    }

    /**
     * Test stress scenario with resource constraints
     * 
     * @test
     */
    public function testStressScenarioWithResourceConstraints(): void
    {
        // Arrange: Create stress test scenario
        $stressIterations = 100;
        $apiKey = 'stress-test-key';
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $initialMemory = memory_get_usage(true);
        $startTime = microtime(true);
        
        $successCount = 0;
        $errorCount = 0;
        
        // Act: Run stress test
        for ($i = 1; $i <= $stressIterations; $i++) {
            try {
                // Alternate between different types of requests
                if ($i % 2 === 0) {
                    // Export request
                    $request = $this->mockFactory->createHttpRequestMock([
                        'action' => 'export',
                        'page' => $i
                    ]);
                    $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
                    
                    $result = $this->exportController->execute();
                } else {
                    // Shipment notification
                    $shipmentData = [
                        'order_number' => "STRESS-ORD-{$i}",
                        'shipments' => [
                            [
                                'shipment_id' => "STRESS-SHIP-{$i}",
                                'tracking_number' => "STRESS-TRACK-{$i}",
                                'carrier' => 'UPS'
                            ]
                        ]
                    ];
                    
                    $request = $this->mockFactory->createHttpRequestMock();
                    $request->method('getContent')->willReturn(json_encode($shipmentData));
                    $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
                    
                    $result = $this->shipmentController->execute();
                }
                
                $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
                $successCount++;
                
            } catch (\Exception $e) {
                $errorCount++;
                
                // Allow some errors under stress, but not too many
                $this->assertLessThan($stressIterations * 0.1, $errorCount,
                    "Error count {$errorCount} should be less than 10% of total iterations");
            }
        // 
            if ($i % 25 === 0) {
                $currentMemory = memory_get_usage(true);
                $memoryIncrease = $currentMemory - $initialMemory;
                
                $this->assertLessThan($this->maxMemoryUsage * 2, $memoryIncrease,
                    "Memory usage under stress should not exceed 2x normal limit");
            }
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // Assert: Verify stress test results
        $this->assertGreaterThan($stressIterations * 0.9, $successCount,
            "Success rate should be at least 90%");
        
        $averageTimePerIteration = $totalTime / $stressIterations;
        $this->assertLessThan(1.0, $averageTimePerIteration,
            "Average time per iteration under stress should be under 1 second");
        
        $finalMemory = memory_get_usage(true);
        $totalMemoryIncrease = $finalMemory - $initialMemory;
        
        $this->assertLessThan($this->maxMemoryUsage * 3, $totalMemoryIncrease,
            "Total memory increase under stress should be reasonable");
    }

    /**
     * Test reliability with network simulation errors
     * 
     * @test
     */
    public function testReliabilityWithNetworkErrors(): void
    {
        // Arrange: Simulate network error scenarios
        $apiKey = 'reliability-test-key';
        $testIterations = 20;
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $successCount = 0;
        $recoverableErrorCount = 0;
        $fatalErrorCount = 0;
        
        // Act: Test with simulated network issues
        for ($i = 1; $i <= $testIterations; $i++) {
            try {
                // Simulate different error conditions
                $errorSimulation = $i % 4;
                
                switch ($errorSimulation) {
                    case 0: // Normal request
                        $request = $this->mockFactory->createHttpRequestMock([
                            'action' => 'export'
                        ]);
                        break;
                    case 1: // Timeout simulation (slow request)
                        $request = $this->mockFactory->createHttpRequestMock([
                            'action' => 'export',
                            'simulate_timeout' => true
                        ]);
                        break;
                    case 2: // Malformed data
                        $request = $this->mockFactory->createHttpRequestMock([
                            'action' => 'export',
                            'malformed_data' => 'invalid'
                        ]);
                        break;
                    case 3: // Large payload
                        $request = $this->mockFactory->createHttpRequestMock([
                            'action' => 'export',
                            'large_payload' => str_repeat('data', 10000)
                        ]);
                        break;
                }
                
                $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
                
                $result = $this->exportController->execute();
                
                $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
                $successCount++;
                
            } catch (\Exception $e) {
                // Categorize errors
                if (strpos($e->getMessage(), 'timeout') !== false || 
                    strpos($e->getMessage(), 'network') !== false) {
                    $recoverableErrorCount++;
                } else {
                    $fatalErrorCount++;
                }
            }
        }
        // 
        $successRate = ($successCount / $testIterations) * 100;
        $this->assertGreaterThan(70, $successRate,
            "Success rate {$successRate}% should be at least 70% even with network issues");
        
        $this->assertLessThan($testIterations * 0.2, $fatalErrorCount,
            "Fatal errors should be less than 20% of total requests");
        
        // Recoverable errors are acceptable under network stress
        $this->assertLessThan($testIterations * 0.5, $recoverableErrorCount,
            "Recoverable errors should be less than 50% of total requests");
    }
}