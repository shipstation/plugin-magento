<?php

namespace Auctane\Api\Tests\Mock\Config;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Mock implementation of Magento's ScopeConfigInterface
 * Provides configuration value retrieval functionality for testing
 */
class ScopeConfig
{
    /**
     * @var TestCase
     */
    private TestCase $testCase;

    /**
     * @var array
     */
    private array $configValues = [];

    /**
     * Constructor
     *
     * @param TestCase $testCase
     * @param array $configValues Pre-configured values
     */
    public function __construct(TestCase $testCase, array $configValues = [])
    {
        $this->testCase = $testCase;
        $this->configValues = $configValues;
    }

    /**
     * Create mock ScopeConfigInterface instance
     *
     * @return MockObject
     */
    public function createMock(): MockObject
    {
        // Use reflection to access the protected createMock method
        $reflection = new \ReflectionClass($this->testCase);
        $method = $reflection->getMethod('createMock');
        $method->setAccessible(true);
        
        $mock = $method->invoke($this->testCase, 'Magento\Framework\App\Config\ScopeConfigInterface');
        
        $mock->method('getValue')
            ->willReturnCallback([$this, 'getValue']);
            
        $mock->method('isSetFlag')
            ->willReturnCallback([$this, 'isSetFlag']);
            
        return $mock;
    }

    /**
     * Get configuration value
     *
     * @param string $path Configuration path
     * @param string $scopeType Scope type (store, website, default)
     * @param null|string|int $scopeCode Scope code
     * @return mixed
     */
    public function getValue(string $path, string $scopeType = 'default', $scopeCode = null)
    {
        $key = $this->buildConfigKey($path, $scopeType, $scopeCode);
        
        // Try specific scope first, then fall back to default
        if (isset($this->configValues[$key])) {
            return $this->configValues[$key];
        }
        
        if (isset($this->configValues[$path])) {
            return $this->configValues[$path];
        }
        
        return null;
    }

    /**
     * Check if configuration flag is set
     *
     * @param string $path Configuration path
     * @param string $scopeType Scope type (store, website, default)
     * @param null|string|int $scopeCode Scope code
     * @return bool
     */
    public function isSetFlag(string $path, string $scopeType = 'default', $scopeCode = null): bool
    {
        $value = $this->getValue($path, $scopeType, $scopeCode);
        return !empty($value) && $value !== '0';
    }

    /**
     * Set configuration value for testing
     *
     * @param string $path Configuration path
     * @param mixed $value Configuration value
     * @param string $scopeType Scope type
     * @param null|string|int $scopeCode Scope code
     */
    public function setConfigValue(string $path, $value, string $scopeType = 'default', $scopeCode = null): void
    {
        $key = $this->buildConfigKey($path, $scopeType, $scopeCode);
        $this->configValues[$key] = $value;
    }

    /**
     * Build configuration key for scoped values
     *
     * @param string $path Configuration path
     * @param string $scopeType Scope type
     * @param null|string|int $scopeCode Scope code
     * @return string
     */
    private function buildConfigKey(string $path, string $scopeType, $scopeCode): string
    {
        if ($scopeCode !== null) {
            return "{$scopeType}:{$scopeCode}:{$path}";
        }
        
        if ($scopeType !== 'default') {
            return "{$scopeType}:{$path}";
        }
        
        return $path;
    }
}