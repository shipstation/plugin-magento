<?php

namespace Auctane\Api\Tests\Utilities;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Auctane\Api\Tests\Mock\Framework\ObjectManager;
use Auctane\Api\Tests\Mock\Config\ScopeConfig;
use Auctane\Api\Tests\Mock\Store\StoreManager;
use Auctane\Api\Tests\Mock\Framework\Controller\Result\JsonFactoryMock;

/**
 * Factory class for creating consistent mock objects for Magento dependencies
 * Used across all test classes to ensure uniform mocking behavior
 */
class MockFactory
{
    /**
     * @var TestCase
     */
    private TestCase $testCase;

    /**
     * Constructor
     *
     * @param TestCase $testCase
     */
    public function __construct(TestCase $testCase = null)
    {
        if ($testCase) {
            $this->testCase = $testCase;
        }
    }

    /**
     * Set the test case instance for creating mocks
     *
     * @param TestCase $testCase
     */
    public function setTestCase(TestCase $testCase): void
    {
        $this->testCase = $testCase;
    }

    /**
     * Create a mock ScopeConfigInterface with predefined configuration values
     *
     * @param array $values Configuration values keyed by path
     * @return MockObject
     */
    public function createScopeConfigMock(array $values = []): MockObject
    {
        $scopeConfig = new ScopeConfig($this->testCase, $values);
        return $scopeConfig->createMock();
    }

    /**
     * Create a mock StoreManagerInterface with store configuration
     *
     * @param array $stores Store configuration data
     * @return MockObject
     */
    public function createStoreManagerMock(array $stores = []): MockObject
    {
        $storeManager = new StoreManager($this->testCase, $stores);
        return $storeManager->createMock();
    }



    /**
     * Create a mock JsonFactory for creating JSON responses
     *
     * @return MockObject
     */
    public function createJsonFactoryMock(): MockObject
    {
        $jsonFactory = new JsonFactoryMock($this->testCase);
        return $jsonFactory->createMock();
    }

    /**
     * Create a mock JSON response object
     *
     * @param array $data Response data
     * @return MockObject
     */
    public function createJsonResponseMock(array $data = []): MockObject
    {
        $mock = $this->createMock('Magento\Framework\Controller\Result\Json');
        
        $responseData = null;
        $httpResponseCode = 200;
        
        $mock->method('setData')
            ->willReturnCallback(function ($data) use (&$responseData, $mock) {
                $responseData = $data;
                return $mock;
            });
            
        $mock->method('setHttpResponseCode')
            ->willReturnCallback(function ($code) use (&$httpResponseCode, $mock) {
                $httpResponseCode = $code;
                return $mock;
            });
            
        $mock->method('getData')
            ->willReturnCallback(function () use (&$responseData) {
                return $responseData;
            });
            
        $mock->method('getHttpResponseCode')
            ->willReturnCallback(function () use (&$httpResponseCode) {
                return $httpResponseCode;
            });
            
        // Set initial data if provided
        if (!empty($data)) {
            $responseData = $data;
        }
        
        return $mock;
    }

    /**
     * Create a mock HTTP request object
     *
     * @param array $params Request parameters
     * @param array $headers HTTP headers
     * @return MockObject
     */
    public function createHttpRequestMock(array $params = [], array $headers = []): MockObject
    {
        $mock = $this->createMock('Magento\Framework\App\Request\Http');
        
        $mock->method('getParam')
            ->willReturnCallback(function ($key, $default = null) use ($params) {
                return $params[$key] ?? $default;
            });
            
        $mock->method('getParams')
            ->willReturn($params);
            
        $mock->method('getHeader')
            ->willReturnCallback(function ($name) use ($headers) {
                return $headers[$name] ?? null;
            });
            
        $mock->method('getHeaders')
            ->willReturn($headers);
            
        $mock->method('getContent')
            ->willReturn(json_encode($params));
            
        $mock->method('isPost')
            ->willReturn(true);
            
        return $mock;
    }

    /**
     * Create a mock ObjectManager for dependency injection
     *
     * @param array $services Service mappings
     * @return MockObject
     */
    public function createObjectManagerMock(array $services = []): MockObject
    {
        $objectManager = new ObjectManager($this->testCase, $services);
        return $objectManager->createMock();
    }

    /**
     * Create a mock Logger for testing logging functionality
     *
     * @return MockObject
     */
    public function createLoggerMock(): MockObject
    {
        $mock = $this->createMock('Psr\Log\LoggerInterface');
        
        // Track logged messages for assertions
        $loggedMessages = [];
        
        foreach (['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'] as $level) {
            $mock->method($level)
                ->willReturnCallback(function ($message, array $context = []) use (&$loggedMessages, $level) {
                    $loggedMessages[] = [
                        'level' => $level,
                        'message' => $message,
                        'context' => $context
                    ];
                });
        }
        
        $mock->method('log')
            ->willReturnCallback(function ($level, $message, array $context = []) use (&$loggedMessages) {
                $loggedMessages[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context
                ];
            });
            
        // Add method to retrieve logged messages for testing
        $mock->getLoggedMessages = function () use (&$loggedMessages) {
            return $loggedMessages;
        };
        
        return $mock;
    }

    /**
     * Create a mock Context for controllers
     *
     * @return MockObject
     */
    public function createContextMock(): MockObject
    {
        $mock = $this->createMock('Magento\Framework\App\Action\Context');
        
        $request = $this->createHttpRequestMock();
        $response = $this->createMock('Magento\Framework\App\Response\Http');
        $objectManager = $this->createObjectManagerMock();
        
        $mock->method('getRequest')->willReturn($request);
        $mock->method('getResponse')->willReturn($response);
        $mock->method('getObjectManager')->willReturn($objectManager);
        
        return $mock;
    }

    /**
     * Create a mock Authorization service
     *
     * @param bool $isAuthorized Default authorization result
     * @return MockObject
     */
    public function createAuthorizationMock(bool $isAuthorized = true): MockObject
    {
        $mock = $this->createMock('Auctane\Api\Model\Authorization');
        
        $mock->method('isAuthorized')
            ->willReturn($isAuthorized);
            
        return $mock;
    }

    /**
     * Create a mock Export action
     *
     * @return MockObject
     */
    public function createExportActionMock(): MockObject
    {
        $mock = $this->createMock('Auctane\Api\Model\Action\Export');
        
        $mock->method('execute')
            ->willReturn([
                'orders' => [],
                'page' => 1,
                'pages' => 1,
                'total' => 0
            ]);
            
        return $mock;
    }

    /**
     * Create a mock ShipNotify action
     *
     * @return MockObject
     */
    public function createShipNotifyActionMock(): MockObject
    {
        $mock = $this->createMock('Auctane\Api\Model\Action\ShipNotify');
        
        $mock->method('execute')
            ->willReturn([
                'success' => true,
                'shipments_processed' => 1,
                'packages_processed' => 1
            ]);
            
        return $mock;
    }

    /**
     * Create a mock InventoryFetch action
     *
     * @return MockObject
     */
    public function createInventoryFetchActionMock(): MockObject
    {
        $mock = $this->createMock('Auctane\Api\Model\Action\InventoryFetch');
        
        $mock->method('execute')
            ->willReturn([
                'products' => [],
                'total' => 0
            ]);
            
        return $mock;
    }

    /**
     * Create a mock InventoryPush action
     *
     * @return MockObject
     */
    public function createInventoryPushActionMock(): MockObject
    {
        $mock = $this->createMock('Auctane\Api\Model\Action\InventoryPush');
        
        $mock->method('execute')
            ->willReturn([
                'success' => true,
                'products_updated' => 0
            ]);
            
        return $mock;
    }

    /**
     * Create a mock Order Repository
     *
     * @return MockObject
     */
    public function createOrderRepositoryMock(): MockObject
    {
        $mock = $this->createMock('Magento\Sales\Api\OrderRepositoryInterface');
        
        $mock->method('getList')
            ->willReturn([]);
            
        $mock->method('get')
            ->willReturn($this->createOrderMock());
            
        return $mock;
    }

    /**
     * Create a mock Order
     *
     * @return MockObject
     */
    public function createOrderMock(): MockObject
    {
        $mock = $this->createMock('Magento\Sales\Model\Order');
        
        $mock->method('getIncrementId')
            ->willReturn('000000001');
            
        $mock->method('getStatus')
            ->willReturn('processing');
            
        return $mock;
    }

    /**
     * Create a mock Shipment Repository
     *
     * @return MockObject
     */
    public function createShipmentRepositoryMock(): MockObject
    {
        $mock = $this->createMock('Magento\Sales\Api\ShipmentRepositoryInterface');
        
        $mock->method('save')
            ->willReturnArgument(0);
            
        return $mock;
    }

    /**
     * Create a mock Track Repository
     *
     * @return MockObject
     */
    public function createTrackRepositoryMock(): MockObject
    {
        $mock = $this->createMock('Magento\Sales\Api\ShipmentTrackRepositoryInterface');
        
        $mock->method('save')
            ->willReturnArgument(0);
            
        return $mock;
    }

    /**
     * Configure ScopeConfig mock with values for specific scope
     *
     * @param array $values Configuration values
     * @param string $scope Scope type (default, website, store)
     * @param int $scopeId Scope ID
     */
    public function configureScopeConfigMock(array $values, string $scope = 'default', int $scopeId = 0): void
    {
        // This method would be used to configure existing scope config mocks
        // Implementation depends on how the ScopeConfig mock is structured
    }

    /**
     * Create a generic mock object
     *
     * @param string $className Class name to mock
     * @return MockObject
     */
    private function createMock(string $className): MockObject
    {
        if (!$this->testCase) {
            throw new \RuntimeException('TestCase must be set before creating mocks');
        }
        
        // Use reflection to access the protected createMock method
        $reflection = new \ReflectionClass($this->testCase);
        $method = $reflection->getMethod('createMock');
        $method->setAccessible(true);
        
        return $method->invoke($this->testCase, $className);
    }
}