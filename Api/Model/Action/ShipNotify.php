<?php

namespace Auctane\Api\Model\Action;

use Auctane\Api\Exception\InvalidXmlException;
use Auctane\Api\Helper\Data;
use Auctane\Api\Model\OrderDoesNotExistException;
use Auctane\Api\Model\ShipmentCannotBeCreatedForOrderException;
use Exception;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\ShipmentExtensionFactory;
use Magento\Sales\Api\Data\ShipmentExtensionInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Sales\Model\OrderFactory;
use SimpleXMLElement;

class ShipNotify
{
    /**
     * Invoice Comment
     */
    const COMMENT = 'Issued by Auctane ShipStation.';
    /**
     * Mails Disabled Configuration Path
     */
    const MAILS_DISABLED = 'system/smtp/disable';
    /**
     * Shipments Enabled Configuration Path
     */
    const SHIPMENTS_ENABLED = 'sales_email/shipment/enabled';
    /**
     * Order factory
     *
     * @var OrderFactory
     */
    private $_orderFactory;
    /**
     * Scope config interface
     *
     * @var ScopeConfigInterface
     */
    private $_scopeConfig;
    /**
     * Transaction factory
     *
     * @var TransactionFactory
     */
    private $_transactionFactory;
    /**
     * Shipment factory
     *
     * @var ShipmentFactory
     */
    private $_shipmentFactory;
    /**
     * Invoice sender
     *
     * @var InvoiceSender
     */
    private $_invoiceSender;
    /**
     * Shipment sender
     *
     * @var Magento\Sales\Model\Order\Email\Sender\ShipmentSender
     */
    private $_shipmentSender;
    /**
     * Track factory
     *
     * @var TrackFactory
     */
    private $_trackFactory;
    /**
     * Helper
     *
     * @var Data
     */
    private $_dataHelper;
    /**
     * Import child
     *
     * @var boolean
     */
    private $_importChild = 0;
    /**
     * Custom Invoicing
     *
     * @var boolean
     */
    private $_customInvoicing = 0;
    /**
     * Scope interface
     *
     * @var \Magento\Store\Model\ScopeInterface
     */
    private $_store = '';
    /**
     * Product type
     *
     * @var Type
     */
    private $_typeBundle = '';
    /** @var DirectoryList */
    private $directoryList;
    /**
     * Mails Enabled
     *
     * @var boolean
     */
    private $_mailsEnabled = 0;

    /** @var ShipmentExtensionFactory */
    private $shipmentExtensionFactory;

    /**
     * Shipnotify contructor
     *
     * @param OrderFactory $orderFactory order factory
     * @param ScopeConfigInterface $scopeConfig scope config
     * @param TransactionFactory $transactionFactory transaction
     * @param ShipmentFactory $shipmentFactory shipment
     * @param InvoiceSender $invoiceSender invoice
     * @param ShipmentSender $shipmentSender shipment
     * @param TrackFactory $trackFactory track
     * @param Data $dataHelper helper
     *
     * @param DirectoryList $directoryList
     * @param ShipmentExtensionFactory $shipmentExtensionFactory
     */
    public function __construct(
        OrderFactory $orderFactory,
        ScopeConfigInterface $scopeConfig,
        TransactionFactory $transactionFactory,
        ShipmentFactory $shipmentFactory,
        InvoiceSender $invoiceSender,
        ShipmentSender $shipmentSender,
        TrackFactory $trackFactory,
        Data $dataHelper,
        DirectoryList $directoryList,
        ShipmentExtensionFactory $shipmentExtensionFactory
    ) {
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_transactionFactory = $transactionFactory;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_shipmentSender = $shipmentSender;
        $this->_trackFactory = $trackFactory;
        $this->_dataHelper = $dataHelper;
        $this->directoryList = $directoryList;
        $this->shipmentExtensionFactory = $shipmentExtensionFactory;

        $this->_store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        //Check for the import child items for the bundle product
        $importChild = 'shipstation_general/shipstation/import_child_products';
        $this->_importChild = $this->_scopeConfig->getValue(
            $importChild,
            $this->_store
        );

        // Settings to check custom/auto invoice is enabled on not
        $customInvoicing = 'shipstation_general/shipstation/custom_invoicing';
        $this->_customInvoicing = $this->_scopeConfig->getValue(
            $customInvoicing,
            $this->_store
        );

        // Settings to check mails/shipments are enabled on not
        $mailSetting = $this->_scopeConfig->getValue(self::MAILS_DISABLED, $this->_store); //if mailSetting is 0 which means mails are enabled.
        $shipmentSetting = $this->_scopeConfig->getValue(self::SHIPMENTS_ENABLED, $this->_store);
        if ($mailSetting == 0 && $shipmentSetting == 1) {
            $this->_mailsEnabled = 1;
        }

        $this->_typeBundle = Type::TYPE_BUNDLE;
    }

    /**
     * Perform a notify using POSTed data.
     * See Auctane API specification.
     * @return string
     * @throws InvalidXmlException
     * @throws OrderDoesNotExistException
     * @throws ShipmentCannotBeCreatedForOrderException
     * @throws FileSystemException
     * @throws LocalizedException
     * @throws Exception
     */
    public function process(): string
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_file('php://input');

        if (!$xml) {
            throw new InvalidXmlException(libxml_get_errors());
        }

        if ($this->_scopeConfig->getValue('shipstation_general/shipstation/debug_mode')) {
            $time = time();
            $xml->asXML("{$this->directoryList->getPath(DirectoryList::LOG)}/shipnotify-{$time}.log");
        }

        $order = $this->_getOrder($xml->OrderID);
        $qtys = $this->_getOrderItemQtys($xml->Items, $order);

        if ($order->canInvoice() && !$this->_customInvoicing) {
            // 'NotifyCustomer' must be "true" or "yes" to trigger email
            $notify = filter_var($xml->NotifyCustomer, FILTER_VALIDATE_BOOLEAN);

            $invoice = $order->prepareInvoice($qtys);
            $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
            $invoice->addComment(self::COMMENT, $notify);
            $invoice->register();

            $order->setIsInProcess(true);

            $this->_saveTransaction($order, $invoice);
        }

        $shippingError = $this->_canShip($order);
        if (!empty($shippingError)) {
            throw new ShipmentCannotBeCreatedForOrderException($xml->OrderID, $shippingError);
        }

        $this->_getOrderShipment($order, $qtys, $xml);

        // ShipStation sometimes issue two shipnotify with the same parameters which would cause an invoice email to be
        // send twice to a customer. We place the send logic here because the second shipnotify will fail when
        // trying to create an already existing shipment which will prevent this block from being reached.
        if (isset($invoice) && isset($notify) && $notify) {
            $this->_invoiceSender->send($invoice);
        }

        return "";
    }

    /**
     * Check if an order can be shipped. Return detailed errors if not
     *
     * @param Order $order
     * @return array
     */
    private function _canShip(Order $order): array
    {
        $errors = [];
        if ($order->canUnhold() || $order->isPaymentReview()) {
            $errors[] = "Order is in Payment Review state. Please check payment";
        }
        if ($order->getIsVirtual()) {
            $errors[] = "Order is virtual, can't be shipped";
        }
        if ($order->isCanceled()) {
            $errors[] = "The order has been canceled";
        }
        if ($order->getActionFlag(Order::ACTION_FLAG_SHIP) === false) {
            $errors[] = "Order has already been shipped";
        }

        if (!$this->_canShipItems($order)) {
            $errors[] = "No order item can be sent";
        }
        return $errors;
    }

    /**
     * check if at least one item can be shipped
     *
     * @param Order $order
     * @return bool
     */
    private function _canShipItems(Order $order): bool
    {
        foreach ($order->getAllItems() as $item) {
            if ($item->getQtyToShip() > 0 && !$item->getIsVirtual() &&
                !$item->getLockedDoShip() && !($item->getQtyRefunded() == $item->getQtyOrdered())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get order details
     *
     * @param string $orderId order id
     *
     * @return Order
     */
    private function _getOrder($incrementId)
    {
        //$order \Magento\Sales\Model\Order
        $order = $this->_orderFactory->create()->loadByIncrementId($incrementId);
        if (!$order->getIncrementId()) {
            throw new OrderDoesNotExistException($incrementId);
        }

        return $order;
    }

    /**
     * Get order quantity
     *
     * @param object $xmlItems xml
     * @param Order $order order
     *
     * @return item quantity
     */
    private function _getOrderItemQtys($xmlItems, $order)
    {
        $shipAll = !count((array)$xmlItems);
        $qtys = [];
        $skuCount = [];

        foreach ($order->getItems() as $item) {
            /* collect all items qtys if shipall flag is true */
            if ($shipAll) {
                $qtys[$item->getId()] = $item->getQtyOrdered();
                if ($item->getParentItemId()) {
                    $qtys[$item->getParentItemId()] = $item->getQtyOrdered();
                }

                continue;
            }

            // search for item by SKU
            $sku = trim($item->getSku());
            $xmlItemResult = $xmlItems->xpath(
                sprintf('//Item/SKU[text()="%s"]/..', $sku)
            );

            if (count($xmlItemResult) > 1) {
                if (isset($skuCount[$sku])) {
                    $count = $skuCount[$sku];
                    $skuCount[$sku] = $skuCount[$sku] + 1;
                } else {
                    $count = 0;
                    $skuCount[$sku] = 1;
                }

                list($xmlItem) = $xmlItemResult[$count];
            } elseif (is_array($xmlItemResult) && !empty($xmlItemResult)) {
                list($xmlItem) = $xmlItemResult;
            } else {
                $xmlItem = null;
            }

            if ($xmlItem) {
                $itemSku = trim($xmlItem->SKU);
                // store quantity by order item ID, not by SKU
                if ($itemSku == $sku) {
                    $qtys[$item->getId()] = (float)$xmlItem->Quantity;
                    if ($item->getParentItemId()) {
                        $qtys[$item->getParentItemId()] = (float)$xmlItem->Quantity;
                    }

                }

            }

            //Add child products into the shipments
            if (!$this->_importChild) {
                if ($item->getParentItemId()) {
                    //check for the bundle product type
                    $productType = $item->getParentItem()->getProductType();
                    if ($productType == $this->_typeBundle) {
                        $qtys[$item->getId()] = $qtys[$item->getParentItemId()];
                    }

                }

            }

        }

        return $qtys;
    }

    /**
     * @param Order $order
     * @param $type
     * @return $this
     */
    private function _saveTransaction(Order $order, $type): self
    {
        // \Magento\Framework\DB\Transaction $transaction
        $transaction = $this->_transactionFactory->create();
        $transaction->addObject($type)
            ->addObject($order)
            ->save();

        return $this;
    }

    /**
     * @param Order $order
     * @param int[] $qtys
     * @param SimpleXMLElement $xml
     * @return $this
     * @throws LocalizedException
     */
    private function _getOrderShipment(Order $order, array $qtys, SimpleXMLElement $xml): self
    {
        $shipment = $this->_shipmentFactory->create($order, $qtys, [[
            'number' => (string) $xml->TrackingNumber,
            'carrier_code' =>  strtolower((string) $xml->Carrier),
            'title' => strtoupper((string) $xml->Carrier)
        ]]);

        if ($xml->RequestedWarehouse) {
            $this->assignSource($shipment, $xml->RequestedWarehouse);
        }

        // Internal notes are only visible to admin
        if ($xml->InternalNotes) {
            $shipment->addComment($xml->InternalNotes);
        }

        // 'NotifyCustomer' must be "true" or "yes" to trigger an email
        $notify = filter_var($xml->NotifyCustomer, FILTER_VALIDATE_BOOLEAN);

        if ($xml->NotesToCustomer) {
            $shipment->setCustomerNote($xml->NotesToCustomer);
            $shipment->setCustomerNoteNotify($notify);
        }

        $shipment->register();

        $order->setIsInProgress(true);

        $this->_saveTransaction($order, $shipment);

        if ($notify && $this->_mailsEnabled) {
            $this->_shipmentSender->send($shipment);
        }

        return $this;
    }

    /**
     * @param ShipmentInterface $shipment
     * @param string $sourceCode
     * @return $this
     */
    private function assignSource(ShipmentInterface $shipment, string $sourceCode): self
    {
        /** @var ShipmentExtensionInterface|null $shipmentExtension */
        $shipmentExtension = $shipment->getExtensionAttributes();

        if (empty($shipmentExtension)) {
            $shipmentExtension = $this->shipmentExtensionFactory->create();
        }

        $shipmentExtension->setSourceCode($sourceCode);
        $shipment->setExtensionAttributes($shipmentExtension);

        return $this;
    }
}
