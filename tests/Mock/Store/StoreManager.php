<?php

namespace Auctane\Api\Test\Mock\Store;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Mock implementation of Magento's StoreManagerInterface
 * Provides store management functionality for testing
 */
class StoreManager
{
    /**
     * @var TestCase
     */
    private TestCase $testCase;

    /**
     * @var array
     */
    private array $stores = [];

    /**
     * @var MockObject|null
     */
    private ?MockObject $defaultStore = null;

    /**
     * Constructor
     *
     * @param TestCase $testCase
     * @param array $storeConfigs Store configurations
     */
    public function __construct(TestCase $testCase, array $storeConfigs = [])
    {
        $this->testCase = $testCase;
        $this->initializeStores($storeConfigs);
    }

    /**
     * Create mock StoreManagerInterface instance
     *
     * @return MockObject
     */
    public function createMock(): MockObject
    {
        $mock = $this->testCase->createMock('Magento\Store\Model\StoreManagerInterface');
        
        $mock->method('getStore')
            ->willReturnCallback([$this, 'getStore']);
            
        $mock->method('getStores')
            ->willReturnCallback([$this, 'getStores']);
            
        $mock->method('getDefaultStoreView')
            ->willReturnCallback([$this, 'getDefaultStoreView']);
            
        return $mock;
    }

    /**
     * Get store by ID or code
     *
     * @param null|string|int $storeId Store ID or code
     * @return MockObject
     */
    public function getStore($storeId = null): MockObject
    {
        if ($storeId === null) {
            return $this->getDefaultStoreView();
        }

        // Try to find by ID first
        if (is_numeric($storeId)) {
            foreach ($this->stores as $store) {
                if ($store->getId() === (int)$storeId) {
                    return $store;
                }
            }
        }

        // Try to find by code
        foreach ($this->stores as $store) {
            if ($store->getCode() === $storeId) {
                return $store;
            }
        }

        // Return default store if not found
        return $this->getDefaultStoreView();
    }

    /**
     * Get all stores
     *
     * @param bool $withDefault Include default store
     * @param bool $codeKey Use store code as array key
     * @return array
     */
    public function getStores(bool $withDefault = false, bool $codeKey = false): array
    {
        $stores = $this->stores;
        
        if ($codeKey) {
            $result = [];
            foreach ($stores as $store) {
                $result[$store->getCode()] = $store;
            }
            return $result;
        }
        
        return $stores;
    }

    /**
     * Get default store view
     *
     * @return MockObject
     */
    public function getDefaultStoreView(): MockObject
    {
        if ($this->defaultStore === null) {
            $this->defaultStore = $this->createStoreMock([
                'id' => 1,
                'code' => 'default',
                'name' => 'Default Store View',
                'website_id' => 1
            ]);
        }
        
        return $this->defaultStore;
    }

    /**
     * Initialize stores from configuration
     *
     * @param array $storeConfigs Store configurations
     */
    private function initializeStores(array $storeConfigs): void
    {
        if (empty($storeConfigs)) {
            // Create default store if none provided
            $this->stores[] = $this->getDefaultStoreView();
            return;
        }

        foreach ($storeConfigs as $config) {
            $this->stores[] = $this->createStoreMock($config);
        }
    }

    /**
     * Create a mock Store entity
     *
     * @param array $storeData Store configuration
     * @return MockObject
     */
    private function createStoreMock(array $storeData): MockObject
    {
        $mock = $this->testCase->createMock('Magento\Store\Model\Store');
        
        $defaultData = [
            'id' => 1,
            'code' => 'default',
            'name' => 'Default Store View',
            'website_id' => 1,
            'is_active' => true
        ];
        
        $data = array_merge($defaultData, $storeData);
        
        $mock->method('getId')->willReturn($data['id']);
        $mock->method('getCode')->willReturn($data['code']);
        $mock->method('getName')->willReturn($data['name']);
        $mock->method('getWebsiteId')->willReturn($data['website_id']);
        $mock->method('isActive')->willReturn($data['is_active']);
        
        return $mock;
    }
}