<?php

namespace Auctane\Api\Tests\Unit\Model\Action;

use Auctane\Api\Helper\Data;
use Auctane\Api\Model\Action\Export;
use Auctane\Api\Model\WeightAdapter;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\GiftMessage\Helper\Message;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Psr\Log\LoggerInterface;

class ExportTest extends TestCase
{
    private Export $export;
    private CollectionFactory $orderCollectionFactoryMock;
    private ScopeConfigInterface $scopeConfigMock;
    private Config $eavConfigMock;
    private Data $dataHelperMock;
    private Message $giftMessageMock;
    private WeightAdapter $weightAdapterMock;
    private RegionCollectionFactory $regionCollectionFactoryMock;
    private LoggerInterface $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->eavConfigMock = $this->createMock(Config::class);
        $this->dataHelperMock = $this->createMock(Data::class);
        $this->giftMessageMock = $this->createMock(Message::class);
        $this->weightAdapterMock = $this->createMock(WeightAdapter::class);
        $this->regionCollectionFactoryMock = $this->createMock(RegionCollectionFactory::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        
        // Mock scope config values for constructor initialization
        $this->scopeConfigMock->method('getValue')
            ->willReturnMap([
                ['shipstation_general/shipstation/export_price', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 0],
                ['shipstation_general/shipstation/import_discounts', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 0],
                ['shipstation_general/shipstation/import_child_products', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 0],
                ['shipstation_general/shipstation/attribute', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, ''],
                ['shipstation_general/shipstation/upc_mapping', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, '']
            ]);
        
        $this->export = new Export(
            $this->orderCollectionFactoryMock,
            $this->scopeConfigMock,
            $this->eavConfigMock,
            $this->dataHelperMock,
            $this->giftMessageMock,
            $this->weightAdapterMock,
            $this->regionCollectionFactoryMock,
            $this->loggerMock
        );
    }

    /**
     * Test successful order export with valid date range
     */
    public function testProcessWithValidDateRange(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, '2023-01-01 00:00:00'],
                ['end_date', null, '2023-01-31 23:59:59'],
                ['page', null, 1]
            ]);
        
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderCollectionMock->method('addAttributeToSort')->willReturnSelf();
        $orderCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $orderCollectionMock->method('setPage')->willReturnSelf();
        $orderCollectionMock->method('getLastPageNumber')->willReturn(1);
        $orderCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));
        
        $this->orderCollectionFactoryMock
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $result = $this->export->process($requestMock, []);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<Orders pages="1">', $result);
        $this->assertStringContainsString('</Orders>', $result);
    }

    /**
     * Test export with missing date parameters
     */
    public function testProcessWithMissingDates(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, null],
                ['end_date', null, null],
                ['page', null, 1]
            ]);
        
        $result = $this->export->process($requestMock, []);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<date>date required</date>', $result);
    }

    /**
     * Test export with invalid date format
     */
    public function testProcessWithInvalidDateFormat(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, 'invalid-date'],
                ['end_date', null, 'invalid-date'],
                ['page', null, 1]
            ]);
        
        $result = $this->export->process($requestMock, []);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<date>date required</date>', $result);
    }

    /**
     * Test export with URL encoded dates
     */
    public function testProcessWithUrlEncodedDates(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, urlencode('2023-01-01 00:00:00')],
                ['end_date', null, urlencode('2023-01-31 23:59:59')],
                ['page', null, 1]
            ]);
        
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderCollectionMock->method('addAttributeToSort')->willReturnSelf();
        $orderCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $orderCollectionMock->method('setPage')->willReturnSelf();
        $orderCollectionMock->method('getLastPageNumber')->willReturn(1);
        $orderCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));
        
        $this->orderCollectionFactoryMock
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $result = $this->export->process($requestMock, []);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<Orders pages="1">', $result);
    }

    /**
     * Test export with specific store IDs filter
     */
    public function testProcessWithStoreIdsFilter(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, '2023-01-01 00:00:00'],
                ['end_date', null, '2023-01-31 23:59:59'],
                ['page', null, 1]
            ]);
        
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderCollectionMock->method('addAttributeToSort')->willReturnSelf();
        $orderCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $orderCollectionMock->method('setPage')->willReturnSelf();
        $orderCollectionMock->method('getLastPageNumber')->willReturn(1);
        $orderCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));
        
        $this->orderCollectionFactoryMock
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $storeIds = [1, 2, 3];
        $result = $this->export->process($requestMock, $storeIds);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<Orders pages="1">', $result);
    }

    /**
     * Test export with pagination
     */
    public function testProcessWithPagination(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, '2023-01-01 00:00:00'],
                ['end_date', null, '2023-01-31 23:59:59'],
                ['page', null, 2]
            ]);
        
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderCollectionMock->method('addAttributeToSort')->willReturnSelf();
        $orderCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $orderCollectionMock->method('setPage')->willReturnSelf();
        $orderCollectionMock->method('getLastPageNumber')->willReturn(3);
        $orderCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));
        
        $this->orderCollectionFactoryMock
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $result = $this->export->process($requestMock, []);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<Orders pages="3">', $result);
    }

    /**
     * Test export with orders containing data
     */
    public function testProcessWithOrdersData(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, '2023-01-01 00:00:00'],
                ['end_date', null, '2023-01-31 23:59:59'],
                ['page', null, 1]
            ]);
        
        // Create mock order
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('getIncrementId')->willReturn('000000001');
        $orderMock->method('getCreatedAt')->willReturn('2023-01-15 10:30:00');
        $orderMock->method('getStatus')->willReturn('processing');
        $orderMock->method('getUpdatedAt')->willReturn('2023-01-15 11:00:00');
        $orderMock->method('getOrderCurrencyCode')->willReturn('USD');
        $orderMock->method('getShippingDescription')->willReturn('Standard Shipping');
        $orderMock->method('getShippingMethod')->willReturn('flatrate_flatrate');
        $orderMock->method('getGrandTotal')->willReturn(100.00);
        $orderMock->method('getTaxAmount')->willReturn(8.50);
        $orderMock->method('getShippingAmount')->willReturn(5.00);
        $orderMock->method('getGiftMessageId')->willReturn(null);
        $orderMock->method('getCustomerEmail')->willReturn('test@example.com');
        $orderMock->method('getBillingAddress')->willReturn(null);
        $orderMock->method('getShippingAddress')->willReturn(null);
        $orderMock->method('getItems')->willReturn([]);
        $orderMock->method('getStatusHistoryCollection')->willReturn([]);
        $orderMock->method('getDiscountAmount')->willReturn(0);
        
        // Mock payment
        $paymentMock = $this->createMock(\Magento\Sales\Model\Order\Payment::class);
        $paymentMock->method('getMethod')->willReturn('checkmo');
        $orderMock->method('getPayment')->willReturn($paymentMock);
        
        // Mock store
        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->method('getCode')->willReturn('default');
        $orderMock->method('getStore')->willReturn($storeMock);
        
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderCollectionMock->method('addAttributeToSort')->willReturnSelf();
        $orderCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $orderCollectionMock->method('setPage')->willReturnSelf();
        $orderCollectionMock->method('getLastPageNumber')->willReturn(1);
        $orderCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([$orderMock]));
        
        $this->orderCollectionFactoryMock
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $result = $this->export->process($requestMock, []);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<Orders pages="1">', $result);
        $this->assertStringContainsString('<Order>', $result);
        $this->assertStringContainsString('<OrderNumber><![CDATA[000000001]]></OrderNumber>', $result);
        $this->assertStringContainsString('<OrderStatus><![CDATA[processing]]></OrderStatus>', $result);
        $this->assertStringContainsString('<CurrencyCode><![CDATA[USD]]></CurrencyCode>', $result);
    }

    /**
     * Test export with empty store IDs array
     */
    public function testProcessWithEmptyStoreIds(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, '2023-01-01 00:00:00'],
                ['end_date', null, '2023-01-31 23:59:59'],
                ['page', null, 1]
            ]);
        
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderCollectionMock->method('addAttributeToSort')->willReturnSelf();
        $orderCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $orderCollectionMock->method('setPage')->willReturnSelf();
        $orderCollectionMock->method('getLastPageNumber')->willReturn(1);
        $orderCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));
        
        $this->orderCollectionFactoryMock
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $result = $this->export->process($requestMock, []);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<Orders pages="1">', $result);
    }

    /**
     * Test export with default page parameter
     */
    public function testProcessWithDefaultPage(): void
    {
        $requestMock = $this->createMock(HttpRequest::class);
        $requestMock->method('getParam')
            ->willReturnMap([
                ['start_date', null, '2023-01-01 00:00:00'],
                ['end_date', null, '2023-01-31 23:59:59'],
                ['page', null, null] // No page parameter provided
            ]);
        
        $orderCollectionMock = $this->createMock(Collection::class);
        $orderCollectionMock->method('addAttributeToSort')->willReturnSelf();
        $orderCollectionMock->method('addAttributeToFilter')->willReturnSelf();
        $orderCollectionMock->method('setPage')->willReturnSelf();
        $orderCollectionMock->method('getLastPageNumber')->willReturn(1);
        $orderCollectionMock->method('getIterator')->willReturn(new \ArrayIterator([]));
        
        $this->orderCollectionFactoryMock
            ->method('create')
            ->willReturn($orderCollectionMock);
        
        $result = $this->export->process($requestMock, []);
        
        $this->assertStringContainsString('<?xml version="1.0" encoding="utf-16"?>', $result);
        $this->assertStringContainsString('<Orders pages="1">', $result);
    }
}