<?php

namespace Auctane\Api\Tests\Unit\Controller\SalesOrdersExport;

use Auctane\Api\Api\AuthorizationInterface;
use Auctane\Api\Controller\SalesOrdersExport\Index;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Model\OrderSourceAPI\Responses\SalesOrdersExportResponse;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Catalog\Helper\Image;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\GiftMessage\Helper\Message;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ProductRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for SalesOrdersExport Index controller
 * Tests order export functionality with mocked dependencies
 */
class SalesOrdersExportIndexTest extends TestCase
{
    /**
     * @var Index
     */
    private $salesOrdersExportController;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authHandler;

    /**
     * @var JsonFactory|MockObject
     */
    private $jsonFactory;

    /**
     * @var Json|MockObject
     */
    private $jsonResponse;

    /**
     * @var Http|MockObject
     */
    private $request;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks for all dependencies
        $this->authHandler = $this->createMock(AuthorizationInterface::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonResponse = $this->mockFactory->createJsonResponseMock();
        $this->request = $this->mockFactory->createHttpRequestMock();
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $shipmentRepository = $this->createMock(ShipmentRepositoryInterface::class);
        $productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $sortOrderBuilder = $this->createMock(SortOrderBuilder::class);
        $imageHelper = $this->createMock(Image::class);
        $giftMessageProvider = $this->createMock(Message::class);
        $regionCollection = $this->createMock(CollectionFactory::class);
        
        // Configure JsonFactory to return our mock response
        $this->jsonFactory->method('create')->willReturn($this->jsonResponse);
        
        // Create controller instance with dependencies
        $this->salesOrdersExportController = new Index(
            $this->orderRepository,
            $shipmentRepository,
            $productRepository,
            $this->searchCriteriaBuilder,
            $sortOrderBuilder,
            $imageHelper,
            $giftMessageProvider,
            $regionCollection
        );
        
        // Inject remaining dependencies using reflection
        $this->injectDependencies();
    }   
 /**
     * Inject dependencies into the controller using reflection
     */
    private function injectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->salesOrdersExportController);
        
        // Inject JsonFactory
        $jsonFactoryProperty = $reflection->getProperty('jsonFactory');
        $jsonFactoryProperty->setAccessible(true);
        $jsonFactoryProperty->setValue($this->salesOrdersExportController, $this->jsonFactory);
        
        // Inject Request
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->salesOrdersExportController, $this->request);
        
        // Inject AuthHandler
        $authHandlerProperty = $reflection->getProperty('authHandler');
        $authHandlerProperty->setAccessible(true);
        $authHandlerProperty->setValue($this->salesOrdersExportController, $this->authHandler);
    }

    /**
     * Test successful order export with basic request
     */
    public function testExecuteActionWithBasicRequest(): void
    {
        // Arrange
        $requestData = [
            'criteria' => [
                'from_date_time' => '2023-01-01T00:00:00Z',
                'to_date_time' => '2023-12-31T23:59:59Z'
            ]
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        $this->setupOrderRepositoryMock();

        // Act
        $result = $this->salesOrdersExportController->executeAction();

        // Assert
        $this->assertInstanceOf(SalesOrdersExportResponse::class, $result);
        $this->assertIsArray($result->sales_orders);
    }

    /**
     * Test order export with no results
     */
    public function testExecuteActionWithNoResults(): void
    {
        // Arrange
        $requestData = [
            'criteria' => [
                'from_date_time' => '2023-01-01T00:00:00Z'
            ]
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        $this->setupOrderRepositoryMockWithNoResults();

        // Act
        $result = $this->salesOrdersExportController->executeAction();

        // Assert
        $this->assertInstanceOf(SalesOrdersExportResponse::class, $result);
        $this->assertEmpty($result->sales_orders);
    }

    /**
     * Test order export with invalid cursor throws BadRequestException
     */
    public function testExecuteActionWithInvalidCursorThrowsException(): void
    {
        // Arrange
        $requestData = [
            'criteria' => [],
            'cursor' => json_encode([
                'page' => 'invalid',
                'page_size' => 10
            ])
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);

        // Assert
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('cursor page "invalid" is invalid');

        // Act
        $this->salesOrdersExportController->executeAction();
    }

    /**
     * Test authorization failure
     */
    public function testExecuteWithAuthorizationFailure(): void
    {
        // Arrange
        $this->setupAuthorizationMock(false);

        // Act
        $result = $this->salesOrdersExportController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $this->jsonResponse->getData();
        $this->assertEquals('failure', $responseData['status']);
        $this->assertEquals(401, $this->jsonResponse->getHttpResponseCode());
    }    
/**
     * Setup authorization mock
     */
    private function setupAuthorizationMock(bool $isAuthorized): void
    {
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn('Bearer valid-token');
            
        $this->authHandler->method('isAuthorized')
            ->with('valid-token')
            ->willReturn($isAuthorized);
    }

    /**
     * Setup request mock with JSON content
     */
    private function setupRequestMock(array $requestData): void
    {
        $this->request->method('getContent')
            ->willReturn(json_encode($requestData));
    }

    /**
     * Setup order repository mock with sample orders
     */
    private function setupOrderRepositoryMock(): void
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getEntityId')->willReturn(1);
        $order->method('getIncrementId')->willReturn('000000001');
        $order->method('getStatus')->willReturn('processing');
        $order->method('getOrderCurrencyCode')->willReturn('USD');
        $order->method('getCreatedAt')->willReturn('2023-01-01 00:00:00');
        $order->method('getUpdatedAt')->willReturn('2023-01-01 00:00:00');
        $order->method('getCustomerId')->willReturn(1);
        $order->method('getCustomerName')->willReturn('John Doe');
        $order->method('getCustomerEmail')->willReturn('john@example.com');
        $order->method('getItems')->willReturn([]);
        $order->method('getBillingAddress')->willReturn(null);
        $order->method('getShippingAddress')->willReturn(null);
        $order->method('getStore')->willReturn($this->createMock(\Magento\Store\Api\Data\StoreInterface::class));
        $order->method('getPayment')->willReturn($this->createMock(\Magento\Sales\Api\Data\OrderPaymentInterface::class));
        $order->method('getInvoiceCollection')->willReturn([]);
        $order->method('getStatusHistoryCollection')->willReturn([]);
        
        $searchResults = $this->createMock(SearchResultsInterface::class);
        $searchResults->method('getTotalCount')->willReturn(1);
        $searchResults->method('getItems')->willReturn([$order]);
        
        $this->orderRepository->method('getList')->willReturn($searchResults);
        $this->setupSearchCriteriaBuilder();
    }

    /**
     * Setup order repository mock with no results
     */
    private function setupOrderRepositoryMockWithNoResults(): void
    {
        $searchResults = $this->createMock(SearchResultsInterface::class);
        $searchResults->method('getTotalCount')->willReturn(0);
        $searchResults->method('getItems')->willReturn([]);
        
        $this->orderRepository->method('getList')->willReturn($searchResults);
        $this->setupSearchCriteriaBuilder();
    }

    /**
     * Setup search criteria builder mock
     */
    private function setupSearchCriteriaBuilder(): void
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);
        
        $this->searchCriteriaBuilder->method('addFilter')
            ->willReturnSelf();
            
        $this->searchCriteriaBuilder->method('setPageSize')
            ->willReturnSelf();
            
        $this->searchCriteriaBuilder->method('setCurrentPage')
            ->willReturnSelf();
            
        $this->searchCriteriaBuilder->method('create')
            ->willReturn($searchCriteria);
    }
}