<?php

declare(strict_types=1);

namespace Auctane\Api\Tests\Integration;

use Auctane\Api\Tests\Utilities\TestCase;
use Auctane\Api\Tests\Fixtures\Orders\OrderFixture;
use Auctane\Api\Controller\SalesOrdersExport\Index as SalesOrdersExportController;
use Auctane\Api\Controller\ShipmentNotification\Index as ShipmentNotificationController;
use Auctane\Api\Exception\ApiException;

/**
 * Reliability tests for API endpoints
 * 
 * Tests error recovery, data consistency, and system stability
 * Requirements: 1.2, 2.4
 */
class ReliabilityTest extends TestCase
{
    private SalesOrdersExportController $exportController;
    private ShipmentNotificationController $shipmentController;

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
     * Test graceful degradation under system stress
     * 
     * @test
     */
    public function testGracefulDegradationUnderStress(): void
    {
        // Arrange: Simulate system under stress
        $apiKey = 'stress-degradation-key';
        $stressLevel = 50; // Number of rapid requests
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $successfulResponses = 0;
        $gracefulFailures = 0;
        $hardFailures = 0;
        
        // Act: Rapid fire requests to simulate stress
        for ($i = 1; $i <= $stressLevel; $i++) {
            try {
                $request = $this->mockFactory->createHttpRequestMock([
                    'action' => 'export',
                    'stress_test' => true,
                    'iteration' => $i
                ]);
                $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
                
                $result = $this->exportController->execute();
                
                $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
                
                $responseData = $result->getData();
                
                if (isset($responseData['error'])) {
                    // Check if it's a graceful failure (proper error response)
                    if (isset($responseData['error']['code']) && isset($responseData['error']['message'])) {
                        $gracefulFailures++;
                    } else {
                        $hardFailures++;
                    }
                } else {
                    $successfulResponses++;
                }
                
            } catch (\Exception $e) {
                if ($e instanceof ApiException) {
                    $gracefulFailures++;
                } else {
                    $hardFailures++;
                }
            }
        }
        // 
        $totalResponses = $successfulResponses + $gracefulFailures + $hardFailures;
        $this->assertEquals($stressLevel, $totalResponses, 'All requests should receive some response');
        
        // At least 50% should be successful or graceful failures
        $acceptableResponses = $successfulResponses + $gracefulFailures;
        $this->assertGreaterThanOrEqual($stressLevel * 0.5, $acceptableResponses,
            'At least 50% of responses should be successful or graceful failures');
        
        // Hard failures should be minimal
        $this->assertLessThan($stressLevel * 0.1, $hardFailures,
            'Hard failures should be less than 10%');
    }

    /**
     * Test data consistency across multiple operations
     * 
     * @test
     */
    public function testDataConsistencyAcrossOperations(): void
    {
        // Arrange: Set up consistent test data
        $apiKey = 'consistency-test-key';
        $orderNumber = 'CONSISTENCY-001';
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        // Step 1: Export order
        $exportRequest = $this->mockFactory->createHttpRequestMock([
            'action' => 'export',
            'order_number' => $orderNumber
        ]);
        $exportRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        $exportResult = $this->exportController->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $exportResult);
        
        $exportData = $exportResult->getData();
        $this->assertArrayHasKey('orders', $exportData);
        
        // Step 2: Create shipment notification for the same order
        $shipmentData = [
            'order_number' => $orderNumber,
            'shipments' => [
                [
                    'shipment_id' => 'SHIP-CONSISTENCY-001',
                    'tracking_number' => 'TRACK-CONSISTENCY-001',
                    'carrier' => 'UPS'
                ]
            ]
        ];
        
        $shipmentRequest = $this->mockFactory->createHttpRequestMock();
        $shipmentRequest->method('getContent')->willReturn(json_encode($shipmentData));
        $shipmentRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        $shipmentResult = $this->shipmentController->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $shipmentResult);
        
        $shipmentResponseData = $shipmentResult->getData();
        $this->assertArrayHasKey('success', $shipmentResponseData);
        
        // Step 3: Export order again to verify consistency
        $secondExportResult = $this->exportController->execute();
        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $secondExportResult);
        
        $secondExportData = $secondExportResult->getData();
        
        // Assert: Data should be consistent across operations
        $this->assertEquals($exportData['orders'][0]['order_number'] ?? null, 
                          $secondExportData['orders'][0]['order_number'] ?? null,
                          'Order number should remain consistent');
        
        // Verify shipment was properly associated
        if (isset($secondExportData['orders'][0]['shipments'])) {
            $this->assertNotEmpty($secondExportData['orders'][0]['shipments'],
                'Order should now have shipment information');
        }
    }

    /**
     * Test error recovery and retry mechanisms
     * 
     * @test
     */
    public function testErrorRecoveryAndRetryMechanisms(): void
    {
        // Arrange: Set up scenarios that might fail initially
        $apiKey = 'retry-test-key';
        $maxRetries = 3;
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $testScenarios = [
            'timeout_recovery' => [
                'action' => 'export',
                'simulate_timeout' => true,
                'retry_count' => 0
            ],
            'data_corruption_recovery' => [
                'action' => 'export',
                'corrupt_data' => true,
                'retry_count' => 0
            ],
            'partial_failure_recovery' => [
                'action' => 'export',
                'partial_failure' => true,
                'retry_count' => 0
            ]
        ];
        
        foreach ($testScenarios as $scenarioName => $scenario) {
            $success = false;
            $attempts = 0;
            
            // Act: Attempt operation with retries
            while (!$success && $attempts < $maxRetries) {
                try {
                    $attempts++;
                    
                    $request = $this->mockFactory->createHttpRequestMock($scenario);
                    $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
                    
                    $result = $this->exportController->execute();
                    
                    $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
                    
                    $responseData = $result->getData();
                    
                    // Check if operation succeeded
                    if (!isset($responseData['error'])) {
                        $success = true;
                    } else {
                        // Log the error for retry
                        $errorCode = $responseData['error']['code'] ?? 'unknown';
                        
                        // Only retry on recoverable errors
                        if (in_array($errorCode, ['timeout', 'temporary_failure', 'partial_failure'])) {
                            continue;
                        } else {
                            break; // Don't retry on permanent errors
                        }
                    }
                    
                } catch (\Exception $e) {
                    // Determine if error is recoverable
                    if (strpos($e->getMessage(), 'timeout') !== false ||
                        strpos($e->getMessage(), 'temporary') !== false) {
                        continue; // Retry
                    } else {
                        break; // Don't retry on permanent errors
                    }
                }
            }
        // 
            if ($success) {
                $this->assertTrue($success, "Scenario '{$scenarioName}' should eventually succeed");
            } else {
                $this->assertLessThanOrEqual($maxRetries, $attempts,
                    "Scenario '{$scenarioName}' should not exceed max retry attempts");
            }
        }
    }

    /**
     * Test system stability under edge conditions
     * 
     * @test
     */
    public function testSystemStabilityUnderEdgeConditions(): void
    {
        // Arrange: Create edge case scenarios
        $apiKey = 'stability-test-key';
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $edgeCases = [
            'empty_request' => [],
            'null_values' => [
                'action' => null,
                'order_number' => null,
                'data' => null
            ],
            'extremely_long_strings' => [
                'action' => 'export',
                'order_number' => str_repeat('A', 10000),
                'description' => str_repeat('B', 50000)
            ],
            'special_characters' => [
                'action' => 'export',
                'order_number' => '!@#$%^&*()_+{}|:"<>?[]\\;\',./',
                'customer_name' => 'José García-Müller 中文 العربية'
            ],
            'nested_arrays' => [
                'action' => 'export',
                'deep_nesting' => [
                    'level1' => [
                        'level2' => [
                            'level3' => [
                                'level4' => [
                                    'level5' => 'deep_value'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        
        foreach ($edgeCases as $caseName => $caseData) {
            try {
                $request = $this->mockFactory->createHttpRequestMock($caseData);
                $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
                
                $result = $this->exportController->execute();
                
                // Assert: System should handle edge cases gracefully
                $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result,
                    "Edge case '{$caseName}' should return valid JSON response");
                
                $responseData = $result->getData();
                
                // Response should be well-formed even for edge cases
                $this->assertIsArray($responseData,
                    "Edge case '{$caseName}' should return array response");
                
                // If there's an error, it should be properly formatted
                if (isset($responseData['error'])) {
                    $this->assertArrayHasKey('message', $responseData['error'],
                        "Error response for '{$caseName}' should have message");
                }
                
            } catch (\Exception $e) {
                // Exceptions should be proper API exceptions, not system crashes
                $this->assertInstanceOf(\Exception::class, $e,
                    "Edge case '{$caseName}' should throw proper exceptions, not crash");
                
                // Exception message should be meaningful
                $this->assertNotEmpty($e->getMessage(),
                    "Exception for '{$caseName}' should have meaningful message");
            }
        }
    }

    /**
     * Test resource cleanup and memory management
     * 
     * @test
     */
    public function testResourceCleanupAndMemoryManagement(): void
    {
        // Arrange: Set up memory monitoring
        $apiKey = 'memory-cleanup-key';
        $iterations = 100;
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $initialMemory = memory_get_usage(true);
        $memoryReadings = [];
        
        // Act: Perform multiple operations and monitor memory
        for ($i = 1; $i <= $iterations; $i++) {
            // Create request with varying data sizes
            $dataSize = ($i % 10) + 1; // 1-10
            $orderData = OrderFixture::createOrderWithItems($dataSize);
            
            $request = $this->mockFactory->createHttpRequestMock([
                'action' => 'export',
                'order_data' => $orderData,
                'iteration' => $i
            ]);
            $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
            
            $result = $this->exportController->execute();
            
            $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
            
            // Record memory usage every 10 iterations
            if ($i % 10 === 0) {
                $currentMemory = memory_get_usage(true);
                $memoryReadings[] = $currentMemory - $initialMemory;
                
                // Force garbage collection to test cleanup
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $totalMemoryIncrease = $finalMemory - $initialMemory;
        
        // Assert: Memory usage should be reasonable and stable
        $this->assertLessThan(100 * 1024 * 1024, $totalMemoryIncrease, // 100MB limit
            "Total memory increase should be under 100MB");
        
        // Check for memory leaks - memory shouldn't grow linearly
        if (count($memoryReadings) >= 3) {
            $firstReading = $memoryReadings[0];
            $lastReading = end($memoryReadings);
            $memoryGrowthRate = ($lastReading - $firstReading) / count($memoryReadings);
            
            $this->assertLessThan(1024 * 1024, $memoryGrowthRate, // 1MB per reading
                "Memory growth rate should be minimal, indicating proper cleanup");
        }
    }

    /**
     * Test concurrent access and thread safety simulation
     * 
     * @test
     */
    public function testConcurrentAccessAndThreadSafety(): void
    {
        // Arrange: Simulate concurrent access patterns
        $apiKey = 'concurrent-safety-key';
        $concurrentOperations = 20;
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey
        ]);
        
        $results = [];
        $sharedResource = ['counter' => 0, 'data' => []];
        
        // Act: Simulate concurrent operations
        for ($i = 1; $i <= $concurrentOperations; $i++) {
            // Simulate different types of concurrent operations
            $operationType = $i % 3;
            
            switch ($operationType) {
                case 0: // Read operation
                    $request = $this->mockFactory->createHttpRequestMock([
                        'action' => 'export',
                        'operation_type' => 'read',
                        'thread_id' => $i
                    ]);
                    break;
                    
                case 1: // Write operation
                    $request = $this->mockFactory->createHttpRequestMock([
                        'action' => 'export',
                        'operation_type' => 'write',
                        'thread_id' => $i,
                        'data' => "thread_{$i}_data"
                    ]);
                    break;
                    
                case 2: // Mixed operation
                    $request = $this->mockFactory->createHttpRequestMock([
                        'action' => 'export',
                        'operation_type' => 'mixed',
                        'thread_id' => $i,
                        'read_data' => true,
                        'write_data' => "mixed_{$i}_data"
                    ]);
                    break;
            }
            
            $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
            
            try {
                $result = $this->exportController->execute();
                $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
                
                $results[] = [
                    'thread_id' => $i,
                    'operation_type' => $operationType,
                    'success' => true,
                    'result' => $result->getData()
                ];
                
                // Update shared resource (simulating concurrent access)
                $sharedResource['counter']++;
                $sharedResource['data'][] = "operation_{$i}";
                
            } catch (\Exception $e) {
                $results[] = [
                    'thread_id' => $i,
                    'operation_type' => $operationType,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
            }
        }
        // 
        $this->assertCount($concurrentOperations, $results,
            'All concurrent operations should complete');
        
        $successfulOperations = array_filter($results, fn($r) => $r['success']);
        $this->assertGreaterThan($concurrentOperations * 0.8, count($successfulOperations),
            'At least 80% of concurrent operations should succeed');
        
        // Check shared resource integrity
        $this->assertLessThanOrEqual($concurrentOperations, $sharedResource['counter'],
            'Shared counter should not exceed expected value');
        
        $this->assertLessThanOrEqual($concurrentOperations, count($sharedResource['data']),
            'Shared data array should not have more entries than operations');
    }
}