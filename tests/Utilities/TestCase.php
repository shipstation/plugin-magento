<?php

namespace Auctane\Api\Test\Utilities;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Base test case class providing common setup methods and utilities
 * for testing Auctane_Api module components without Magento installation
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * @var MockFactory
     */
    protected MockFactory $mockFactory;

    /**
     * @var DataProvider
     */
    protected DataProvider $dataProvider;

    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockFactory = new MockFactory($this);
        $this->dataProvider = new DataProvider();
    }

    /**
     * Clean up test environment after each test
     */
    protected function tearDown(): void
    {
        // Reset any static state or global variables if needed
        $this->mockFactory = null;
        $this->dataProvider = null;
        parent::tearDown();
    }

    /**
     * Create a mock HTTP request object with optional parameters
     *
     * @param array $params Request parameters
     * @param array $headers HTTP headers
     * @return MockObject
     */
    protected function createMockRequest(array $params = [], array $headers = []): MockObject
    {
        return $this->mockFactory->createHttpRequestMock($params, $headers);
    }

    /**
     * Create a mock JSON response object
     *
     * @param array $data Response data
     * @return MockObject
     */
    protected function createMockResponse(array $data = []): MockObject
    {
        return $this->mockFactory->createJsonResponseMock($data);
    }

    /**
     * Create a mock scope configuration object
     *
     * @param array $config Configuration values
     * @return MockObject
     */
    protected function createMockScopeConfig(array $config = []): MockObject
    {
        return $this->mockFactory->createScopeConfigMock($config);
    }

    /**
     * Create a mock store manager object
     *
     * @param array $stores Store configuration
     * @return MockObject
     */
    protected function createMockStoreManager(array $stores = []): MockObject
    {
        return $this->mockFactory->createStoreManagerMock($stores);
    }

    /**
     * Create test data for a specific scenario
     *
     * @param string $scenario Test scenario name
     * @param array $overrides Data overrides
     * @return array
     */
    protected function createTestData(string $scenario, array $overrides = []): array
    {
        return $this->dataProvider->getTestData($scenario, $overrides);
    }

    /**
     * Assert that an exception is thrown with specific message and code
     *
     * @param string $expectedClass Expected exception class
     * @param string $expectedMessage Expected exception message
     * @param int $expectedCode Expected exception code
     * @param callable $callback Callback that should throw the exception
     */
    protected function assertExceptionThrown(
        string $expectedClass,
        string $expectedMessage,
        int $expectedCode,
        callable $callback
    ): void {
        $this->expectException($expectedClass);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode($expectedCode);
        $callback();
    }

    /**
     * Assert that a JSON response has the expected structure
     *
     * @param array $expectedStructure Expected JSON structure
     * @param array $actualResponse Actual response data
     */
    protected function assertJsonStructure(array $expectedStructure, array $actualResponse): void
    {
        foreach ($expectedStructure as $key => $value) {
            if (is_array($value)) {
                $this->assertArrayHasKey($key, $actualResponse);
                $this->assertJsonStructure($value, $actualResponse[$key]);
            } else {
                $this->assertArrayHasKey($value, $actualResponse);
            }
        }
    }

    /**
     * Create a mock object with fluent interface support
     *
     * @param string $className Class name to mock
     * @param array $methods Methods to mock
     * @return MockObject
     */
    protected function createFluentMock(string $className, array $methods = []): MockObject
    {
        $mock = $this->createMock($className);
        
        foreach ($methods as $method => $returnValue) {
            $mock->method($method)->willReturn($returnValue);
        }
        
        return $mock;
    }

    /**
     * Set up dependency injection container mock
     *
     * @param array $services Service mappings
     * @return MockObject
     */
    protected function createMockObjectManager(array $services = []): MockObject
    {
        return $this->mockFactory->createObjectManagerMock($services);
    }
}