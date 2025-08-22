<?php

namespace Auctane\Api\Test\Mock\Framework;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Mock implementation of Magento's ObjectManager
 * Provides dependency injection container functionality for testing
 */
class ObjectManager
{
    /**
     * @var TestCase
     */
    private TestCase $testCase;

    /**
     * @var array
     */
    private array $services = [];

    /**
     * @var array
     */
    private array $instances = [];

    /**
     * Constructor
     *
     * @param TestCase $testCase
     * @param array $services Pre-configured services
     */
    public function __construct(TestCase $testCase, array $services = [])
    {
        $this->testCase = $testCase;
        $this->services = $services;
    }

    /**
     * Create mock ObjectManager instance
     *
     * @return MockObject
     */
    public function createMock(): MockObject
    {
        $mock = $this->testCase->createMock('Magento\Framework\ObjectManagerInterface');
        
        $mock->method('get')
            ->willReturnCallback([$this, 'get']);
            
        $mock->method('create')
            ->willReturnCallback([$this, 'create']);
            
        return $mock;
    }

    /**
     * Get service instance (singleton pattern)
     *
     * @param string $type Service type/class name
     * @return mixed
     */
    public function get(string $type)
    {
        if (isset($this->instances[$type])) {
            return $this->instances[$type];
        }

        if (isset($this->services[$type])) {
            $this->instances[$type] = $this->services[$type];
            return $this->instances[$type];
        }

        // Create a generic mock for unknown services
        $this->instances[$type] = $this->testCase->createMock($type);
        return $this->instances[$type];
    }

    /**
     * Create new service instance
     *
     * @param string $type Service type/class name
     * @param array $arguments Constructor arguments
     * @return mixed
     */
    public function create(string $type, array $arguments = [])
    {
        if (isset($this->services[$type])) {
            return $this->services[$type];
        }

        // Create a generic mock for unknown services
        return $this->testCase->createMock($type);
    }

    /**
     * Register a service instance
     *
     * @param string $type Service type/class name
     * @param mixed $instance Service instance
     */
    public function addService(string $type, $instance): void
    {
        $this->services[$type] = $instance;
    }
}