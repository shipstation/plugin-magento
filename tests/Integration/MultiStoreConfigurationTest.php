<?php

declare(strict_types=1);

namespace Auctane\Api\Tests\Integration;

use Auctane\Api\Tests\Utilities\TestCase;
use Auctane\Api\Tests\Fixtures\Config\ConfigFixture;
use Auctane\Api\Model\Authorization;
use Auctane\Api\Controller\SalesOrdersExport\Index as SalesOrdersExportController;
use Auctane\Api\Exception\AuthorizationException;

/**
 * Integration tests for multi-store configuration scenarios
 * 
 * Tests API functionality across different store configurations with different API keys
 * Requirements: 3.1, 3.2, 3.3
 */
class MultiStoreConfigurationTest extends TestCase
{
    private Authorization $authorization;
    private SalesOrdersExportController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->authorization = new Authorization(
            $this->mockFactory->createScopeConfigMock(),
            $this->mockFactory->createStoreManagerMock()
        );
        
        $this->controller = new SalesOrdersExportController(
            $this->mockFactory->createContextMock(),
            $this->mockFactory->createJsonFactoryMock(),
            $this->authorization,
            $this->mockFactory->createExportActionMock()
        );
    }

    /**
     * Test authorization with different API keys per store
     * 
     * @test
     */
    public function testMultiStoreApiKeyValidation(): void
    {
        // Arrange: Set up multi-store configuration
        $storeConfigs = ConfigFixture::getMultiStoreConfig();
        
        $store1ApiKey = $storeConfigs['store1']['api_key'];
        $store2ApiKey = $storeConfigs['store2']['api_key'];
        $store3ApiKey = $storeConfigs['store3']['api_key'];
        
        // Test Store 1 authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $store1ApiKey
        ], 'store', 1);
        
        $request1 = $this->mockFactory->createHttpRequestMock();
        $request1->method('getHeader')->with('Authorization')->willReturn("Bearer {$store1ApiKey}");
        $request1->method('getParam')->with('store_id')->willReturn('1');
        
        $result1 = $this->authorization->isAuthorized($request1);
        $this->assertTrue($result1, 'Store 1 should be authorized with its API key');
        
        // Test Store 2 authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $store2ApiKey
        ], 'store', 2);
        
        $request2 = $this->mockFactory->createHttpRequestMock();
        $request2->method('getHeader')->with('Authorization')->willReturn("Bearer {$store2ApiKey}");
        $request2->method('getParam')->with('store_id')->willReturn('2');
        
        $result2 = $this->authorization->isAuthorized($request2);
        $this->assertTrue($result2, 'Store 2 should be authorized with its API key');
        
        // Test Store 3 authorization
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $store3ApiKey
        ], 'store', 3);
        
        $request3 = $this->mockFactory->createHttpRequestMock();
        $request3->method('getHeader')->with('Authorization')->willReturn("Bearer {$store3ApiKey}");
        $request3->method('getParam')->with('store_id')->willReturn('3');
        
        $result3 = $this->authorization->isAuthorized($request3);
        $this->assertTrue($result3, 'Store 3 should be authorized with its API key');
    }

    /**
     * Test cross-store API key rejection
     * 
     * @test
     */
    public function testCrossStoreApiKeyRejection(): void
    {
        // Arrange: Set up multi-store configuration
        $storeConfigs = ConfigFixture::getMultiStoreConfig();
        
        $store1ApiKey = $storeConfigs['store1']['api_key'];
        $store2ApiKey = $storeConfigs['store2']['api_key'];
        
        // Configure Store 1 with its API key
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $store1ApiKey
        ], 'store', 1);
        
        // Try to access Store 1 with Store 2's API key
        $request = $this->mockFactory->createHttpRequestMock();
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$store2ApiKey}");
        $request->method('getParam')->with('store_id')->willReturn('1');
        
        $result = $this->authorization->isAuthorized($request);
        $this->assertFalse($result, 'Store 1 should reject Store 2\'s API key');
    }

    /**
     * Test multi-store order export with different configurations
     * 
     * @test
     */
    public function testMultiStoreOrderExport(): void
    {
        // Arrange: Set up different store configurations
        $storeConfigs = ConfigFixture::getMultiStoreConfig();
        
        foreach ($storeConfigs as $storeId => $config) {
            // Configure each store
            $this->mockFactory->configureScopeConfigMock([
                'auctane_api/general/api_key' => $config['api_key'],
                'auctane_api/general/enabled' => $config['enabled'],
                'auctane_api/export/order_statuses' => $config['order_statuses']
            ], 'store', (int)str_replace('store', '', $storeId));
            
            // Create request for this store
            $request = $this->mockFactory->createHttpRequestMock([
                'action' => 'export',
                'store_id' => str_replace('store', '', $storeId)
            ]);
            $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$config['api_key']}");
            
            // Act: Execute export for this store
            $result = $this->controller->execute();
            
            // Assert: Verify store-specific configuration is applied
            $this->assertInstanceOf(\Magento\Framework\Controller\Result\Json::class, $result);
            
            if ($config['enabled']) {
                $responseData = $result->getData();
                $this->assertArrayHasKey('orders', $responseData);
            }
        }
    }

    /**
     * Test disabled store configuration
     * 
     * @test
     */
    public function testDisabledStoreConfiguration(): void
    {
        // Arrange: Set up disabled store
        $apiKey = 'disabled-store-key';
        
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $apiKey,
            'auctane_api/general/enabled' => false
        ], 'store', 1);
        
        $request = $this->mockFactory->createHttpRequestMock([
            'store_id' => '1'
        ]);
        $request->method('getHeader')->with('Authorization')->willReturn("Bearer {$apiKey}");
        
        // Act & Assert: Verify disabled store rejects requests
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('API is disabled for this store');
        
        $this->authorization->isAuthorized($request);
    }

    /**
     * Test store-specific configuration inheritance
     * 
     * @test
     */
    public function testStoreConfigurationInheritance(): void
    {
        // Arrange: Set up default and store-specific configurations
        $defaultApiKey = 'default-api-key';
        $storeSpecificApiKey = 'store-specific-key';
        
        // Configure default scope
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $defaultApiKey,
            'auctane_api/general/enabled' => true
        ], 'default');
        
        // Configure store-specific override
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $storeSpecificApiKey
        ], 'store', 1);
        
        // Test default configuration (store without override)
        $defaultRequest = $this->mockFactory->createHttpRequestMock();
        $defaultRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {$defaultApiKey}");
        $defaultRequest->method('getParam')->with('store_id')->willReturn('2');
        
        $defaultResult = $this->authorization->isAuthorized($defaultRequest);
        $this->assertTrue($defaultResult, 'Store 2 should use default API key');
        
        // Test store-specific configuration
        $storeRequest = $this->mockFactory->createHttpRequestMock();
        $storeRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {$storeSpecificApiKey}");
        $storeRequest->method('getParam')->with('store_id')->willReturn('1');
        
        $storeResult = $this->authorization->isAuthorized($storeRequest);
        $this->assertTrue($storeResult, 'Store 1 should use store-specific API key');
        
        // Test that store 1 rejects default API key
        $invalidRequest = $this->mockFactory->createHttpRequestMock();
        $invalidRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {$defaultApiKey}");
        $invalidRequest->method('getParam')->with('store_id')->willReturn('1');
        
        $invalidResult = $this->authorization->isAuthorized($invalidRequest);
        $this->assertFalse($invalidResult, 'Store 1 should reject default API key when override exists');
    }

    /**
     * Test multi-store website-level configuration
     * 
     * @test
     */
    public function testMultiWebsiteConfiguration(): void
    {
        // Arrange: Set up website-level configurations
        $website1ApiKey = 'website1-api-key';
        $website2ApiKey = 'website2-api-key';
        
        // Configure Website 1
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $website1ApiKey,
            'auctane_api/general/enabled' => true
        ], 'website', 1);
        
        // Configure Website 2
        $this->mockFactory->configureScopeConfigMock([
            'auctane_api/general/api_key' => $website2ApiKey,
            'auctane_api/general/enabled' => true
        ], 'website', 2);
        
        // Test Website 1 authorization
        $website1Request = $this->mockFactory->createHttpRequestMock();
        $website1Request->method('getHeader')->with('Authorization')->willReturn("Bearer {$website1ApiKey}");
        $website1Request->method('getParam')->with('website_id')->willReturn('1');
        
        $website1Result = $this->authorization->isAuthorized($website1Request);
        $this->assertTrue($website1Result, 'Website 1 should be authorized');
        
        // Test Website 2 authorization
        $website2Request = $this->mockFactory->createHttpRequestMock();
        $website2Request->method('getHeader')->with('Authorization')->willReturn("Bearer {$website2ApiKey}");
        $website2Request->method('getParam')->with('website_id')->willReturn('2');
        
        $website2Result = $this->authorization->isAuthorized($website2Request);
        $this->assertTrue($website2Result, 'Website 2 should be authorized');
        
        // Test cross-website rejection
        $crossWebsiteRequest = $this->mockFactory->createHttpRequestMock();
        $crossWebsiteRequest->method('getHeader')->with('Authorization')->willReturn("Bearer {$website1ApiKey}");
        $crossWebsiteRequest->method('getParam')->with('website_id')->willReturn('2');
        
        $crossWebsiteResult = $this->authorization->isAuthorized($crossWebsiteRequest);
        $this->assertFalse($crossWebsiteResult, 'Website 2 should reject Website 1\'s API key');
    }
}