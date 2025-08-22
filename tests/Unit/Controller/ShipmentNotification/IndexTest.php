<?php

namespace Auctane\Api\Tests\Unit\Controller\ShipmentNotification;

use Auctane\Api\Api\AuthorizationInterface;
use Auctane\Api\Controller\ShipmentNotification\Index;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Model\OrderSourceAPI\Responses\ShipmentNotificationResponse;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Shipping\Model\Order\Track;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Test class for ShipmentNotification Index controller
 * Tests shipment notification functionality with mocked dependencies
 */
class ShipmentNotificationIndexTest extends TestCase
{
    /**
     * @var Index
     */
    private $shipmentNotificationController;

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
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfig;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks for all dependencies
        $this->authHandler = $this->createMock(AuthorizationInterface::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonResponse = $this->mockFactory->createJsonResponseMock();
        $this->request = $this->mockFactory->createHttpRequestMock();
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->scopeConfig = $this->mockFactory->createScopeConfigMock();
        
        $shipOrder = $this->createMock(ShipOrderInterface::class);
        $shipmentFactory = $this->createMock(ShipmentFactory::class);
        $shipmentRepository = $this->createMock(ShipmentRepositoryInterface::class);
        $shipmentTrack = $this->createMock(Track::class);
        $logger = $this->createMock(LoggerInterface::class);
        $transactionFactory = $this->createMock(TransactionFactory::class);
        
        // Configure JsonFactory to return our mock response
        $this->jsonFactory->method('create')->willReturn($this->jsonResponse);
        
        // Create controller instance with dependencies
        $this->shipmentNotificationController = new Index(
            $shipOrder,
            $this->orderRepository,
            $shipmentFactory,
            $shipmentRepository,
            $shipmentTrack,
            $logger,
            $transactionFactory
        );
        
        // Inject remaining dependencies using reflection
        $this->injectDependencies();
    }    /**

     * Inject dependencies into the controller using reflection
     */
    private function injectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->shipmentNotificationController);
        
        // Inject JsonFactory
        $jsonFactoryProperty = $reflection->getProperty('jsonFactory');
        $jsonFactoryProperty->setAccessible(true);
        $jsonFactoryProperty->setValue($this->shipmentNotificationController, $this->jsonFactory);
        
        // Inject Request
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->shipmentNotificationController, $this->request);
        
        // Inject AuthHandler
        $authHandlerProperty = $reflection->getProperty('authHandler');
        $authHandlerProperty->setAccessible(true);
        $authHandlerProperty->setValue($this->shipmentNotificationController, $this->authHandler);
        
        // Inject ScopeConfig
        $scopeConfigProperty = $reflection->getProperty('scopeConfig');
        $scopeConfigProperty->setAccessible(true);
        $scopeConfigProperty->setValue($this->shipmentNotificationController, $this->scopeConfig);
    }

    /**
     * Test successful shipment notification processing
     */
    public function testExecuteActionWithValidNotification(): void
    {
        // Arrange
        $requestData = [
            'notifications' => [
                [
                    'notification_id' => 'notif-123',
                    'order_id' => 1,
                    'tracking_number' => 'TRACK123',
                    'carrier_code' => 'ups',
                    'carrier_service_code' => 'UPS Ground',
                    'notify_buyer' => true,
                    'items' => [
                        [
                            'sku' => 'SKU001',
                            'quantity' => 1
                        ]
                    ]
                ]
            ]
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        $this->setupOrderRepositoryMock();

        // Act
        $result = $this->shipmentNotificationController->executeAction();

        // Assert
        $this->assertInstanceOf(ShipmentNotificationResponse::class, $result);
        $this->assertIsArray($result->notification_results);
        $this->assertCount(1, $result->notification_results);
    }

    /**
     * Test shipment notification with order that cannot be shipped
     */
    public function testExecuteActionWithUnshippableOrder(): void
    {
        // Arrange
        $requestData = [
            'notifications' => [
                [
                    'notification_id' => 'notif-456',
                    'order_id' => 2,
                    'tracking_number' => 'TRACK456',
                    'carrier_code' => 'fedex',
                    'items' => []
                ]
            ]
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        $this->setupOrderRepositoryMockWithUnshippableOrder();

        // Act
        $result = $this->shipmentNotificationController->executeAction();

        // Assert
        $this->assertInstanceOf(ShipmentNotificationResponse::class, $result);
        $this->assertIsArray($result->notification_results);
        $this->assertCount(1, $result->notification_results);
        
        // Check that the result indicates failure
        $notificationResult = $result->notification_results[0];
        $this->assertEquals('notif-456', $notificationResult->notification_id);
        $this->assertEquals('failure', $notificationResult->status);
    }

    /**
     * Test authorization failure
     */
    public function testExecuteWithAuthorizationFailure(): void
    {
        // Arrange
        $this->setupAuthorizationMock(false);

        // Act
        $result = $this->shipmentNotificationController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $this->jsonResponse->getData();
        $this->assertEquals('failure', $responseData['status']);
        $this->assertEquals(401, $this->jsonResponse->getHttpResponseCode());
    }    /**

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
     * Setup order repository mock with shippable order
     */
    private function setupOrderRepositoryMock(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('canInvoice')->willReturn(false);
        $order->method('canUnhold')->willReturn(false);
        $order->method('isPaymentReview')->willReturn(false);
        $order->method('getIsVirtual')->willReturn(false);
        $order->method('isCanceled')->willReturn(false);
        $order->method('getActionFlag')->with(Order::ACTION_FLAG_SHIP)->willReturn(true);
        $order->method('getAllItems')->willReturn([]);
        $order->method('getItems')->willReturn([]);
        
        $this->orderRepository->method('get')
            ->with(1)
            ->willReturn($order);
    }

    /**
     * Setup order repository mock with unshippable order
     */
    private function setupOrderRepositoryMockWithUnshippableOrder(): void
    {
        $order = $this->createMock(Order::class);
        $order->method('getId')->willReturn(2);
        $order->method('canUnhold')->willReturn(false);
        $order->method('isPaymentReview')->willReturn(false);
        $order->method('getIsVirtual')->willReturn(true); // Virtual order cannot be shipped
        $order->method('isCanceled')->willReturn(false);
        
        $this->orderRepository->method('get')
            ->with(2)
            ->willReturn($order);
    }

    /**
     * Test multiple notifications in single request
     */
    public function testExecuteActionWithMultipleNotifications(): void
    {
        // Arrange
        $requestData = [
            'notifications' => [
                [
                    'notification_id' => 'notif-1',
                    'order_id' => 1,
                    'tracking_number' => 'TRACK1',
                    'carrier_code' => 'ups',
                    'items' => []
                ],
                [
                    'notification_id' => 'notif-2',
                    'order_id' => 2,
                    'tracking_number' => 'TRACK2',
                    'carrier_code' => 'fedex',
                    'items' => []
                ]
            ]
        ];
        
        $this->setupAuthorizationMock(true);
        $this->setupRequestMock($requestData);
        
        // Setup multiple orders
        $order1 = $this->createMock(Order::class);
        $order1->method('getId')->willReturn(1);
        $order1->method('canInvoice')->willReturn(false);
        $order1->method('canUnhold')->willReturn(false);
        $order1->method('isPaymentReview')->willReturn(false);
        $order1->method('getIsVirtual')->willReturn(false);
        $order1->method('isCanceled')->willReturn(false);
        $order1->method('getActionFlag')->willReturn(true);
        $order1->method('getAllItems')->willReturn([]);
        $order1->method('getItems')->willReturn([]);
        
        $order2 = $this->createMock(Order::class);
        $order2->method('getId')->willReturn(2);
        $order2->method('getIsVirtual')->willReturn(true); // This one will fail
        
        $this->orderRepository->method('get')
            ->willReturnMap([
                [1, $order1],
                [2, $order2]
            ]);

        // Act
        $result = $this->shipmentNotificationController->executeAction();

        // Assert
        $this->assertInstanceOf(ShipmentNotificationResponse::class, $result);
        $this->assertCount(2, $result->notification_results);
    }
}