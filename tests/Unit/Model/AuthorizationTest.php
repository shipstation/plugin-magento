<?php

namespace Auctane\Api\Tests\Unit\Model;

use Auctane\Api\Model\Authorization;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class AuthorizationTest extends TestCase
{
    private Authorization $authorization;
    private ScopeConfigInterface $scopeConfigMock;
    private StoreManagerInterface $storeManagerMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        
        $this->authorization = new Authorization(
            $this->scopeConfigMock,
            $this->storeManagerMock
        );
    }

    /**
     * Test successful authorization with valid API key from single store
     */
    public function testIsAuthorizedWithValidApiKeyFromSingleStore(): void
    {
        $validApiKey = 'valid-api-key-123';
        $storeId = 1;
        
        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getId')->willReturn($storeId);
        
        $this->storeManagerMock
            ->method('getStores')
            ->willReturn([$storeMock]);
            
        $this->scopeConfigMock
            ->method('getValue')
            ->with(
                'shipstation_general/shipstation/ship_api_key',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($validApiKey);
            
        $result = $this->authorization->isAuthorized($validApiKey);
        
        $this->assertTrue($result);
    }

    /**
     * Test successful authorization with valid API key from multiple stores
     */
    public function testIsAuthorizedWithValidApiKeyFromMultipleStores(): void
    {
        $validApiKey = 'valid-api-key-456';
        $store1Id = 1;
        $store2Id = 2;
        
        $store1Mock = $this->createMock(Store::class);
        $store1Mock->method('getId')->willReturn($store1Id);
        
        $store2Mock = $this->createMock(Store::class);
        $store2Mock->method('getId')->willReturn($store2Id);
        
        $this->storeManagerMock
            ->method('getStores')
            ->willReturn([$store1Mock, $store2Mock]);
            
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturnMap([
                [
                    'shipstation_general/shipstation/ship_api_key',
                    ScopeInterface::SCOPE_STORE,
                    $store1Id,
                    'different-api-key'
                ],
                [
                    'shipstation_general/shipstation/ship_api_key',
                    ScopeInterface::SCOPE_STORE,
                    $store2Id,
                    $validApiKey
                ]
            ]);
            
        $result = $this->authorization->isAuthorized($validApiKey);
        
        $this->assertTrue($result);
    }

    /**
     * Test authorization failure with invalid API key
     */
    public function testIsAuthorizedWithInvalidApiKey(): void
    {
        $invalidApiKey = 'invalid-api-key';
        $validApiKey = 'valid-api-key-789';
        $storeId = 1;
        
        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getId')->willReturn($storeId);
        
        $this->storeManagerMock
            ->method('getStores')
            ->willReturn([$storeMock]);
            
        $this->scopeConfigMock
            ->method('getValue')
            ->with(
                'shipstation_general/shipstation/ship_api_key',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($validApiKey);
            
        $result = $this->authorization->isAuthorized($invalidApiKey);
        
        $this->assertFalse($result);
    }

    /**
     * Test authorization failure with empty API key configuration
     */
    public function testIsAuthorizedWithEmptyApiKeyConfiguration(): void
    {
        $testApiKey = 'test-api-key';
        $storeId = 1;
        
        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getId')->willReturn($storeId);
        
        $this->storeManagerMock
            ->method('getStores')
            ->willReturn([$storeMock]);
            
        $this->scopeConfigMock
            ->method('getValue')
            ->with(
                'shipstation_general/shipstation/ship_api_key',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn('');
            
        $result = $this->authorization->isAuthorized($testApiKey);
        
        $this->assertFalse($result);
    }

    /**
     * Test authorization failure with null API key configuration
     */
    public function testIsAuthorizedWithNullApiKeyConfiguration(): void
    {
        $testApiKey = 'test-api-key';
        $storeId = 1;
        
        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getId')->willReturn($storeId);
        
        $this->storeManagerMock
            ->method('getStores')
            ->willReturn([$storeMock]);
            
        $this->scopeConfigMock
            ->method('getValue')
            ->with(
                'shipstation_general/shipstation/ship_api_key',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn(null);
            
        $result = $this->authorization->isAuthorized($testApiKey);
        
        $this->assertFalse($result);
    }

    /**
     * Test authorization with no stores configured
     */
    public function testIsAuthorizedWithNoStores(): void
    {
        $testApiKey = 'test-api-key';
        
        $this->storeManagerMock
            ->method('getStores')
            ->willReturn([]);
            
        $result = $this->authorization->isAuthorized($testApiKey);
        
        $this->assertFalse($result);
    }

    /**
     * Test authorization with empty token
     */
    public function testIsAuthorizedWithEmptyToken(): void
    {
        $validApiKey = 'valid-api-key';
        $storeId = 1;
        
        $storeMock = $this->createMock(Store::class);
        $storeMock->method('getId')->willReturn($storeId);
        
        $this->storeManagerMock
            ->method('getStores')
            ->willReturn([$storeMock]);
            
        $this->scopeConfigMock
            ->method('getValue')
            ->with(
                'shipstation_general/shipstation/ship_api_key',
                ScopeInterface::SCOPE_STORE,
                $storeId
            )
            ->willReturn($validApiKey);
            
        $result = $this->authorization->isAuthorized('');
        
        $this->assertFalse($result);
    }

    /**
     * Test authorization with multiple stores having different API keys
     */
    public function testIsAuthorizedWithMultipleStoresDifferentApiKeys(): void
    {
        $testApiKey = 'test-api-key';
        $store1ApiKey = 'store1-api-key';
        $store2ApiKey = 'store2-api-key';
        $store1Id = 1;
        $store2Id = 2;
        
        $store1Mock = $this->createMock(Store::class);
        $store1Mock->method('getId')->willReturn($store1Id);
        
        $store2Mock = $this->createMock(Store::class);
        $store2Mock->method('getId')->willReturn($store2Id);
        
        $this->storeManagerMock
            ->method('getStores')
            ->willReturn([$store1Mock, $store2Mock]);
            
        $this->scopeConfigMock
            ->method('getValue')
            ->willReturnMap([
                [
                    'shipstation_general/shipstation/ship_api_key',
                    ScopeInterface::SCOPE_STORE,
                    $store1Id,
                    $store1ApiKey
                ],
                [
                    'shipstation_general/shipstation/ship_api_key',
                    ScopeInterface::SCOPE_STORE,
                    $store2Id,
                    $store2ApiKey
                ]
            ]);
            
        $result = $this->authorization->isAuthorized($testApiKey);
        
        $this->assertFalse($result);
    }
}