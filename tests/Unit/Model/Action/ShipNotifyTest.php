<?php

namespace Auctane\Api\Tests\Unit\Model\Action;

use Auctane\Api\Exception\InvalidXmlException;
use Auctane\Api\Helper\Data;
use Auctane\Api\Model\Action\ShipNotify;
use Auctane\Api\Model\OrderDoesNotExistException;
use Auctane\Api\Model\ShipmentCannotBeCreatedForOrderException;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\ShipmentExtensionFactory;
use Magento\Sales\Api\Data\ShipmentExtensionInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\OrderFactory;

class ShipNotifyTest extends TestCase
{
    private ShipNotify $shipNotify;
    private OrderFactory $orderFactoryMock;
    private ScopeConfigInterface $scopeConfigMock;
    private TransactionFactory $transactionFactoryMock;
    private ShipmentFactory $shipmentFactoryMock;
    private InvoiceSender $invoiceSenderMock;
    private ShipmentSender $shipmentSenderMock;
    private TrackFactory $trackFactoryMock;
    private Data $dataHelperMock;
    private DirectoryList $directoryListMock;
    private ShipmentExtensionFactory $shipmentExtensionFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->orderFactoryMock = $this->createMock(OrderFactory::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->transactionFactoryMock = $this->createMock(TransactionFactory::class);
        $this->shipmentFactoryMock = $this->createMock(ShipmentFactory::class);
        $this->invoiceSenderMock = $this->createMock(InvoiceSender::class);
        $this->shipmentSenderMock = $this->createMock(ShipmentSender::class);
        $this->trackFactoryMock = $this->createMock(TrackFactory::class);
        $this->dataHelperMock = $this->createMock(Data::class);
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->shipmentExtensionFactoryMock = $this->createMock(ShipmentExtensionFactory::class);
        
        // Mock scope config values for constructor initialization
        $this->scopeConfigMock->method('getValue')
            ->willReturnMap([
                ['shipstation_general/shipstation/import_child_products', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 0],
                ['shipstation_general/shipstation/custom_invoicing', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 0],
                ['system/smtp/disable', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 0],
                ['sales_email/shipment/enabled', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, null, 1],
                ['shipstation_general/shipstation/debug_mode', null, null, false]
            ]);
        
        $this->shipNotify = new ShipNotify(
            $this->orderFactoryMock,
            $this->scopeConfigMock,
            $this->transactionFactoryMock,
            $this->shipmentFactoryMock,
            $this->invoiceSenderMock,
            $this->shipmentSenderMock,
            $this->trackFactoryMock,
            $this->dataHelperMock,
            $this->directoryListMock,
            $this->shipmentExtensionFactoryMock
        );
    }

    /**
     * Test process method throws InvalidXmlException for malformed XML
     */
    public function testProcessThrowsInvalidXmlExceptionForMalformedXml(): void
    {
        $this->expectException(InvalidXmlException::class);
        
        // Mock php://input with invalid XML
        $invalidXml = '<?xml version="1.0"?><InvalidXml><unclosed>';
        
        // Create a temporary file to simulate php://input
        $tempFile = tmpfile();
        fwrite($tempFile, $invalidXml);
        rewind($tempFile);
        
        // Override php://input stream
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestPhpInputWrapper::class);
        TestPhpInputWrapper::$data = $invalidXml;
        
        try {
            $this->shipNotify->process();
        } finally {
            // Restore original php stream wrapper
            stream_wrapper_restore('php');
            fclose($tempFile);
        }
    }

    /**
     * Test process method throws OrderDoesNotExistException for non-existent order
     */
    public function testProcessThrowsOrderDoesNotExistException(): void
    {
        $this->expectException(OrderDoesNotExistException::class);
        
        $validXml = '<?xml version="1.0"?><ShipNotify><OrderID>999999</OrderID></ShipNotify>';
        
        // Mock order factory to return order that doesn't exist
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('loadByIncrementId')->willReturnSelf();
        $orderMock->method('getIncrementId')->willReturn(null); // Order doesn't exist
        
        $this->orderFactoryMock->method('create')->willReturn($orderMock);
        
        // Override php://input stream
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestPhpInputWrapper::class);
        TestPhpInputWrapper::$data = $validXml;
        
        try {
            $this->shipNotify->process();
        } finally {
            stream_wrapper_restore('php');
        }
    }

    /**
     * Test process method throws ShipmentCannotBeCreatedForOrderException for canceled order
     */
    public function testProcessThrowsShipmentCannotBeCreatedExceptionForCanceledOrder(): void
    {
        $this->expectException(ShipmentCannotBeCreatedForOrderException::class);
        
        $validXml = '<?xml version="1.0"?><ShipNotify><OrderID>000000001</OrderID><Items></Items></ShipNotify>';
        
        // Mock order that exists but is canceled
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('loadByIncrementId')->willReturnSelf();
        $orderMock->method('getIncrementId')->willReturn('000000001');
        $orderMock->method('canInvoice')->willReturn(false);
        $orderMock->method('isCanceled')->willReturn(true);
        $orderMock->method('canUnhold')->willReturn(false);
        $orderMock->method('isPaymentReview')->willReturn(false);
        $orderMock->method('getIsVirtual')->willReturn(false);
        $orderMock->method('getActionFlag')->willReturn(true);
        $orderMock->method('getAllItems')->willReturn([]);
        
        $this->orderFactoryMock->method('create')->willReturn($orderMock);
        
        // Override php://input stream
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestPhpInputWrapper::class);
        TestPhpInputWrapper::$data = $validXml;
        
        try {
            $this->shipNotify->process();
        } finally {
            stream_wrapper_restore('php');
        }
    }

    /**
     * Test successful shipment creation without invoice
     */
    public function testProcessSuccessfulShipmentCreationWithoutInvoice(): void
    {
        $validXml = '<?xml version="1.0"?><ShipNotify><OrderID>000000001</OrderID><TrackingNumber>1234567890</TrackingNumber><Carrier>UPS</Carrier><NotifyCustomer>false</NotifyCustomer><Items></Items></ShipNotify>';
        
        // Mock order that can be shipped but not invoiced
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('loadByIncrementId')->willReturnSelf();
        $orderMock->method('getIncrementId')->willReturn('000000001');
        $orderMock->method('canInvoice')->willReturn(false);
        $orderMock->method('isCanceled')->willReturn(false);
        $orderMock->method('canUnhold')->willReturn(false);
        $orderMock->method('isPaymentReview')->willReturn(false);
        $orderMock->method('getIsVirtual')->willReturn(false);
        $orderMock->method('getActionFlag')->willReturn(true);
        $orderMock->method('setIsInProgress')->willReturnSelf();
        
        // Mock order items
        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getQtyToShip')->willReturn(1);
        $itemMock->method('getIsVirtual')->willReturn(false);
        $itemMock->method('getLockedDoShip')->willReturn(false);
        $itemMock->method('getQtyRefunded')->willReturn(0);
        $itemMock->method('getQtyOrdered')->willReturn(1);
        $itemMock->method('getId')->willReturn(1);
        $itemMock->method('getSku')->willReturn('TEST-SKU');
        $itemMock->method('getParentItemId')->willReturn(null);
        
        $orderMock->method('getAllItems')->willReturn([$itemMock]);
        $orderMock->method('getItems')->willReturn([$itemMock]);
        
        $this->orderFactoryMock->method('create')->willReturn($orderMock);
        
        // Mock shipment creation
        $shipmentMock = $this->createMock(Shipment::class);
        $shipmentMock->method('addComment')->willReturnSelf();
        $shipmentMock->method('setCustomerNote')->willReturnSelf();
        $shipmentMock->method('setCustomerNoteNotify')->willReturnSelf();
        $shipmentMock->method('register')->willReturnSelf();
        $shipmentMock->method('getExtensionAttributes')->willReturn(null);
        $shipmentMock->method('setExtensionAttributes')->willReturnSelf();
        
        $this->shipmentFactoryMock->method('create')->willReturn($shipmentMock);
        
        // Mock transaction
        $transactionMock = $this->createMock(Transaction::class);
        $transactionMock->method('addObject')->willReturnSelf();
        $transactionMock->method('save')->willReturnSelf();
        
        $this->transactionFactoryMock->method('create')->willReturn($transactionMock);
        
        // Mock shipment extension
        $shipmentExtensionMock = $this->createMock(ShipmentExtensionInterface::class);
        $shipmentExtensionMock->method('setSourceCode')->willReturnSelf();
        
        $this->shipmentExtensionFactoryMock->method('create')->willReturn($shipmentExtensionMock);
        
        // Override php://input stream
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestPhpInputWrapper::class);
        TestPhpInputWrapper::$data = $validXml;
        
        try {
            $result = $this->shipNotify->process();
            $this->assertEquals('', $result);
        } finally {
            stream_wrapper_restore('php');
        }
    }

    /**
     * Test successful shipment creation with invoice
     */
    public function testProcessSuccessfulShipmentCreationWithInvoice(): void
    {
        $validXml = '<?xml version="1.0"?><ShipNotify><OrderID>000000001</OrderID><TrackingNumber>1234567890</TrackingNumber><Carrier>UPS</Carrier><NotifyCustomer>true</NotifyCustomer><Items></Items></ShipNotify>';
        
        // Mock order that can be invoiced and shipped
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('loadByIncrementId')->willReturnSelf();
        $orderMock->method('getIncrementId')->willReturn('000000001');
        $orderMock->method('canInvoice')->willReturn(true);
        $orderMock->method('isCanceled')->willReturn(false);
        $orderMock->method('canUnhold')->willReturn(false);
        $orderMock->method('isPaymentReview')->willReturn(false);
        $orderMock->method('getIsVirtual')->willReturn(false);
        $orderMock->method('getActionFlag')->willReturn(true);
        $orderMock->method('setIsInProcess')->willReturnSelf();
        $orderMock->method('setIsInProgress')->willReturnSelf();
        
        // Mock order items
        $itemMock = $this->createMock(Item::class);
        $itemMock->method('getQtyToShip')->willReturn(1);
        $itemMock->method('getIsVirtual')->willReturn(false);
        $itemMock->method('getLockedDoShip')->willReturn(false);
        $itemMock->method('getQtyRefunded')->willReturn(0);
        $itemMock->method('getQtyOrdered')->willReturn(1);
        $itemMock->method('getId')->willReturn(1);
        $itemMock->method('getSku')->willReturn('TEST-SKU');
        $itemMock->method('getParentItemId')->willReturn(null);
        
        $orderMock->method('getAllItems')->willReturn([$itemMock]);
        $orderMock->method('getItems')->willReturn([$itemMock]);
        
        // Mock invoice creation
        $invoiceMock = $this->createMock(Invoice::class);
        $invoiceMock->method('setRequestedCaptureCase')->willReturnSelf();
        $invoiceMock->method('addComment')->willReturnSelf();
        $invoiceMock->method('register')->willReturnSelf();
        
        $orderMock->method('prepareInvoice')->willReturn($invoiceMock);
        
        $this->orderFactoryMock->method('create')->willReturn($orderMock);
        
        // Mock shipment creation
        $shipmentMock = $this->createMock(Shipment::class);
        $shipmentMock->method('addComment')->willReturnSelf();
        $shipmentMock->method('setCustomerNote')->willReturnSelf();
        $shipmentMock->method('setCustomerNoteNotify')->willReturnSelf();
        $shipmentMock->method('register')->willReturnSelf();
        $shipmentMock->method('getExtensionAttributes')->willReturn(null);
        $shipmentMock->method('setExtensionAttributes')->willReturnSelf();
        
        $this->shipmentFactoryMock->method('create')->willReturn($shipmentMock);
        
        // Mock transaction
        $transactionMock = $this->createMock(Transaction::class);
        $transactionMock->method('addObject')->willReturnSelf();
        $transactionMock->method('save')->willReturnSelf();
        
        $this->transactionFactoryMock->method('create')->willReturn($transactionMock);
        
        // Mock shipment extension
        $shipmentExtensionMock = $this->createMock(ShipmentExtensionInterface::class);
        $shipmentExtensionMock->method('setSourceCode')->willReturnSelf();
        
        $this->shipmentExtensionFactoryMock->method('create')->willReturn($shipmentExtensionMock);
        
        // Override php://input stream
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestPhpInputWrapper::class);
        TestPhpInputWrapper::$data = $validXml;
        
        try {
            $result = $this->shipNotify->process();
            $this->assertEquals('', $result);
        } finally {
            stream_wrapper_restore('php');
        }
    }

    /**
     * Test process with virtual order throws exception
     */
    public function testProcessWithVirtualOrderThrowsException(): void
    {
        $this->expectException(ShipmentCannotBeCreatedForOrderException::class);
        
        $validXml = '<?xml version="1.0"?><ShipNotify><OrderID>000000001</OrderID><Items></Items></ShipNotify>';
        
        // Mock virtual order
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('loadByIncrementId')->willReturnSelf();
        $orderMock->method('getIncrementId')->willReturn('000000001');
        $orderMock->method('canInvoice')->willReturn(false);
        $orderMock->method('isCanceled')->willReturn(false);
        $orderMock->method('canUnhold')->willReturn(false);
        $orderMock->method('isPaymentReview')->willReturn(false);
        $orderMock->method('getIsVirtual')->willReturn(true); // Virtual order
        $orderMock->method('getActionFlag')->willReturn(true);
        $orderMock->method('getAllItems')->willReturn([]);
        
        $this->orderFactoryMock->method('create')->willReturn($orderMock);
        
        // Override php://input stream
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestPhpInputWrapper::class);
        TestPhpInputWrapper::$data = $validXml;
        
        try {
            $this->shipNotify->process();
        } finally {
            stream_wrapper_restore('php');
        }
    }

    /**
     * Test process with order in payment review throws exception
     */
    public function testProcessWithOrderInPaymentReviewThrowsException(): void
    {
        $this->expectException(ShipmentCannotBeCreatedForOrderException::class);
        
        $validXml = '<?xml version="1.0"?><ShipNotify><OrderID>000000001</OrderID><Items></Items></ShipNotify>';
        
        // Mock order in payment review
        $orderMock = $this->createMock(Order::class);
        $orderMock->method('loadByIncrementId')->willReturnSelf();
        $orderMock->method('getIncrementId')->willReturn('000000001');
        $orderMock->method('canInvoice')->willReturn(false);
        $orderMock->method('isCanceled')->willReturn(false);
        $orderMock->method('canUnhold')->willReturn(false);
        $orderMock->method('isPaymentReview')->willReturn(true); // Payment review
        $orderMock->method('getIsVirtual')->willReturn(false);
        $orderMock->method('getActionFlag')->willReturn(true);
        $orderMock->method('getAllItems')->willReturn([]);
        
        $this->orderFactoryMock->method('create')->willReturn($orderMock);
        
        // Override php://input stream
        stream_wrapper_unregister('php');
        stream_wrapper_register('php', TestPhpInputWrapper::class);
        TestPhpInputWrapper::$data = $validXml;
        
        try {
            $this->shipNotify->process();
        } finally {
            stream_wrapper_restore('php');
        }
    }
}

/**
 * Test helper class to mock php://input stream
 */
class TestPhpInputWrapper
{
    public static $data = '';
    private $position = 0;

    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $this->position = 0;
        return true;
    }

    public function stream_read($count)
    {
        $ret = substr(static::$data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_eof()
    {
        return $this->position >= strlen(static::$data);
    }

    public function stream_stat()
    {
        return [];
    }
}