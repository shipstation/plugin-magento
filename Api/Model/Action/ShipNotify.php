<?php

namespace Auctane\Api\Model\Action;

use Auctane\Api\Model\OrderDoesNotExistException;
use Auctane\Api\Model\ShipmentCannotBeCreatedForOrderException;
use Exception;

class ShipNotify
{
    /**
     * Invoice Comment
     */
    const COMMENT = 'Issued by Auctane ShipStation.';

    /**
     * Order factory
     *
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $_orderFactory;

    /**
     * Scope config interface
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * Transaction factory
     *
     * @var \Magento\Framework\DB\TransactionFactory
     */
    private $_transactionFactory;

    /**
     * Shipment factory
     *
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    private $_shipmentFactory;

    /**
     * Invoice sender
     *
     * @var \Magento\Sales\Model\Order\Email\Sender\InvoiceSender
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
     * @var \Magento\Sales\Model\Order\Shipment\TrackFactory
     */
    private $_trackFactory;

    /**
     * Helper
     *
     * @var \Auctane\Api\Helper\Data
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
     * @var \Magento\Catalog\Model\Product\Type
     */
    private $_typeBundle = '';

    /**
     * Shipnotify contructor
     *
     * @param \OrderFactory $orderFactory order factory
     * @param \ScopeConfigInterface $scopeConfig scope config
     * @param \TransactionFactory $transactionFactory transaction
     * @param \ShipmentFactory $shipmentFactory shipment
     * @param \InvoiceSender $invoiceSender invoice
     * @param \ShipmentSender $shipmentSender shipment
     * @param \TrackFactory $trackFactory track
     * @param \Data $dataHelper helper
     *
     * @return boolean
     */
    public function __construct(
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
        \Magento\Sales\Model\Order\Email\Sender\ShipmentSender $shipmentSender,
        \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory,
        \Auctane\Api\Helper\Data $dataHelper
    ) {
        $this->_orderFactory = $orderFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_transactionFactory = $transactionFactory;
        $this->_shipmentFactory = $shipmentFactory;
        $this->_invoiceSender = $invoiceSender;
        $this->_shipmentSender = $shipmentSender;
        $this->_trackFactory = $trackFactory;
        $this->_dataHelper = $dataHelper;

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

        $this->_typeBundle = \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE;
    }

    /**
     * Perform a notify using POSTed data.
     * See Auctane API specification.
     *
     * @return Exception
     */
    public function process()
    {
        // Raw XML is POSTed to this stream
        $xml = simplexml_load_file('php://input');
        // load some objects
        try {
            $order = $this->_getOrder($xml->OrderID);
            $qtys = $this->_getOrderItemQtys($xml->Items, $order);
            if ($order->canInvoice() && !$this->_customInvoicing) {
                // 'NotifyCustomer' must be "true" or "yes" to trigger email
                $notify = filter_var($xml->NotifyCustomer, FILTER_VALIDATE_BOOLEAN);
                $invoice = $order->prepareInvoice($qtys);
                $capture = \Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE;
                $invoice->setRequestedCaptureCase($capture);
                $invoice->addComment(self::COMMENT, $notify);
                $invoice->register();
                $order->setIsInProcess(true); // updates status on save
                //Save the invoice transaction
                $this->_saveTransaction($order, $invoice);
                if ($notify) {
                    $this->_invoiceSender->send($invoice);
                }

            }

            if ($order->canShip()) {
                $shipment = $this->_getOrderShipment($order, $qtys, $xml);
            } else {
                throw new ShipmentCannotBeCreatedForOrderException($xml->OrderID);
            }

        } catch (Exception $fault) {
            return $this->_dataHelper->fault($fault->getCode(), $fault->getMessage());
        }
    }

    /**
     * Get order details
     *
     * @param string $orderId order id
     *
     * @return \Magento\Sales\Model\Order
     */
    private function _getOrder($orderId)
    {
        //$order \Magento\Sales\Model\Order
        $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
        if (!$order->getIncrementId()) {
            throw new OrderDoesNotExistException($orderId);
        }

        return $order;
    }

    /**
     * Get order quantity
     *
     * @param object $xmlItems xml
     * @param \Magento\Sales\Model\Order $order order
     *
     * @return item quantity
     */
    private function _getOrderItemQtys($xmlItems, $order)
    {
        $shipAll = !count((array)$xmlItems);
        $qtys = [];
        $skuCount = [];

        /* @var $item Mage_Sales_Model_Order_Item */
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
     * Save the order transaction
     *
     * @param \Magento\Sales\Model\Order $order order
     * @param object $type transaction type
     *
     * @return boolean
     */
    private function _saveTransaction($order, $type)
    {
        // \Magento\Framework\DB\Transaction $transaction
        $transaction = $this->_transactionFactory->create();
        $transaction->addObject($type)
            ->addObject($order)
            ->save();
    }

    /**
     * Order shipment
     *
     * @param \Magento\Sales\Model\Order $order order
     * @param array $qtys quantity
     * @param object $xml xml
     *
     * @return Shipment
     */
    private function _getOrderShipment($order, $qtys, $xml)
    {
        //Set the tracking information.
        $tracking[] = [
            'number' => $xml->TrackingNumber,
            'carrier_code' => $xml->Carrier,
            'title' => strtoupper($xml->Carrier)
        ];
        $shipment = $this->_shipmentFactory->create($order, $qtys, $tracking);
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
        //Save the shipment tranaction
        $this->_saveTransaction($order, $shipment);
        if ($notify) {
            $this->_shipmentSender->send($shipment);
        }

        return $shipment;
    }
}
