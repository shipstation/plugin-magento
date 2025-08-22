<?php

namespace Auctane\Api\Tests\Unit\Controller\InventoryFetch;

use Auctane\Api\Api\AuthorizationInterface;
use Auctane\Api\Controller\InventoryFetch\Index;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Model\OrderSourceAPI\Responses\InventoryFetchResponse;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductSearchResultsInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for InventoryFetch Index controller
 * Tests inventory fetching functionality with mocked dependencies
 */
class IndexTest extends TestCase
{
    /**
     * @var Index
     */
    private $inventoryFetchController;

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
     * @var GetSourceItemsBySkuInterface|MockObject
     */
    private $getSourceItemsBySku;

    /**
     * @var ProductRepositoryInterface|MockObject
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilder;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->authHandler = $this->createMock(AuthorizationInterface::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonResponse = $this->mockFactory->createJsonResponseMock();
        $this->request = $this->mockFactory->createHttpRequestMock();
        $this->getSourceItemsBySku = $this->createMock(GetSourceItemsBySkuInterface::class);
        $this->productRepository = $this->createMock(ProductRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        
        // Configure JsonFactory to return our mock response
        $this->jsonFactory->method('create')->willReturn($this->jsonResponse);
        
        // Create controller instance with dependencies
        $this->inventoryFetchController = new Index(
            $this->getSourceItemsBySku,
            $this->productRepository,
            $this->searchCriteriaBuilder
        );
        
        // Inject remaining dependencies using reflection
        $this->injectDependencies();
    } 
   /**
     * Inject dependencies into the controller using reflection
     */
    private function injectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->inventoryFetchController);
        
        // Inject JsonFactory
        $jsonFactoryProperty = $reflection->getProperty('jsonFactory');
        $jsonFactoryProperty->setAccessible(true);
        $jsonFactoryProperty->setValue($this->inventoryFetchController, $this->jsonFactory);
        
        // Inject Request
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->inventoryFetchController, $this->request);
        
        // Inject AuthHandler
        $authHandlerProperty = $reflection->getProperty('authHandler');
        $authHandlerProperty->setAccessible(true);
        $authHandlerProperty->setValue($this->inventoryFetchController, $this->authHandler);
    }

    /**
     * Test successful inventory fetch with basic request
     */
    public function testExecuteActionWithBasicRequest(): void
    {
        // Arrange
        $requestData = [
            'criteria' => [
                'skus' => ['SKU001', 'SKU002']
            ]
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        $this->setupProductRepositoryMock();
        $this->setupSourceItemsMock();

        // Act
        $result = $this->inventoryFetchController->executeAction();

        // Assert
        $this->assertInstanceOf(InventoryFetchResponse::class, $result);
        $this->assertIsArray($result->items);
    }

    /**
     * Test inventory fetch with pagination
     */
    public function testExecuteActionWithPagination(): void
    {
        // Arrange
        $requestData = [
            'criteria' => [
                'skus' => ['SKU001']
            ],
            'cursor' => json_encode([
                'page' => 2,
                'page_size' => 50
            ])
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        $this->setupProductRepositoryMock(2, 50);
        $this->setupSourceItemsMock();

        // Act
        $result = $this->inventoryFetchController->executeAction();

        // Assert
        $this->assertInstanceOf(InventoryFetchResponse::class, $result);
    }

    /**
     * Test inventory fetch throws BadRequestException for invalid page
     */
    public function testExecuteActionThrowsBadRequestForInvalidPage(): void
    {
        // Arrange
        $requestData = [
            'criteria' => [
                'skus' => ['SKU001']
            ],
            'cursor' => json_encode([
                'page' => 999,
                'page_size' => 10
            ])
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        
        // Setup product repository to return limited results
        $searchResults = $this->createMock(ProductSearchResultsInterface::class);
        $searchResults->method('getTotalCount')->willReturn(5);
        $searchResults->method('getItems')->willReturn([]);
        
        $this->productRepository->method('getList')->willReturn($searchResults);
        $this->setupSearchCriteriaBuilder(999, 10);

        // Assert
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage('Invalid page');

        // Act
        $this->inventoryFetchController->executeAction();
    }    /*
*
     * Test inventory fetch with no cursor (default pagination)
     */
    public function testExecuteActionWithNoCursor(): void
    {
        // Arrange
        $requestData = [
            'criteria' => [
                'skus' => ['SKU001']
            ]
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        $this->setupProductRepositoryMock(1, 100); // Default page and page_size
        $this->setupSourceItemsMock();

        // Act
        $result = $this->inventoryFetchController->executeAction();

        // Assert
        $this->assertInstanceOf(InventoryFetchResponse::class, $result);
    }

    /**
     * Test authorization failure
     */
    public function testExecuteWithAuthorizationFailure(): void
    {
        // Arrange
        $this->setupAuthorizationMock(false);

        // Act
        $result = $this->inventoryFetchController->execute();

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
     * Setup product repository mock
     */
    private function setupProductRepositoryMock(int $page = 1, int $pageSize = 100): void
    {
        $product = $this->createMock(ProductInterface::class);
        $product->method('getSku')->willReturn('SKU001');
        $product->method('getName')->willReturn('Test Product');
        
        $searchResults = $this->createMock(ProductSearchResultsInterface::class);
        $searchResults->method('getTotalCount')->willReturn(1);
        $searchResults->method('getItems')->willReturn([$product]);
        
        $this->productRepository->method('getList')->willReturn($searchResults);
        $this->setupSearchCriteriaBuilder($page, $pageSize);
    }

    /**
     * Setup search criteria builder mock
     */
    private function setupSearchCriteriaBuilder(int $page, int $pageSize): void
    {
        $searchCriteria = $this->createMock(SearchCriteria::class);
        
        $this->searchCriteriaBuilder->method('setCurrentPage')
            ->with($page)
            ->willReturnSelf();
            
        $this->searchCriteriaBuilder->method('setPageSize')
            ->with($pageSize)
            ->willReturnSelf();
            
        $this->searchCriteriaBuilder->method('addFilter')
            ->willReturnSelf();
            
        $this->searchCriteriaBuilder->method('create')
            ->willReturn($searchCriteria);
    }

    /**
     * Setup source items mock
     */
    private function setupSourceItemsMock(): void
    {
        $sourceItem = $this->createMock(SourceItemInterface::class);
        $sourceItem->method('getSku')->willReturn('SKU001');
        $sourceItem->method('getSourceCode')->willReturn('default');
        $sourceItem->method('getQuantity')->willReturn(100);
        
        $this->getSourceItemsBySku->method('execute')
            ->willReturn([$sourceItem]);
    }
}