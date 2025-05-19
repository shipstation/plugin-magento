<?php
namespace Auctane\Api\Controller\ShipmentNotification;

use Auctane\Api\Controller\BaseAuthorizedController;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Model\OrderSourceAPI\Models\NoteType;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderItem;
use Auctane\Api\Model\OrderSourceAPI\Models\ShipmentNotificationItem;
use Auctane\Api\Model\OrderSourceAPI\Models\ShipmentNotificationResult;
use Auctane\Api\Model\OrderSourceAPI\Models\ShipmentNotificationStatus;
use Auctane\Api\Model\OrderSourceAPI\Requests\ShipmentNotificationRequest;
use Auctane\Api\Model\OrderSourceAPI\Responses\ShipmentNotificationResponse;
use Exception;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magento\Shipping\Model\Order\Track;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Index extends BaseAuthorizedController implements HttpPostActionInterface
{
    /** @var string Invoice Comment */
    protected const COMMENT = 'Issued by Auctane ShipStation.';
    /** @var OrderRepositoryInterface  */
    protected OrderRepositoryInterface $orderRepository;
    /** @var ShipmentRepositoryInterface  */
    protected ShipmentRepositoryInterface $shipmentRepository;
    /** @var ShipOrderInterface  */
    protected ShipOrderInterface $shipOrder;
    /** @var LoggerInterface  */
    protected LoggerInterface $logger;
    /** @var Track  */
    protected Track $shipmentTrack;
    /** @var TransactionFactory  */
    protected TransactionFactory $transactionFactory;
    /** @var ShipmentFactory  */
    protected ShipmentFactory $shipmentFactory;
    /** @var bool  */
    protected bool $supportsCustomInvoicing;
    /** @var bool|mixed  */
    protected bool $importChildItemsForBundle;
    /** @var bool|mixed  */
    protected bool $autoInvoicingEnabled;
    /** @var bool  */
    protected bool $mailsEnabled;

    /**
     * @param ShipOrderInterface $shipOrder
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentFactory $shipmentFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param Track $shipmentTrack
     * @param LoggerInterface $logger
     * @param TransactionFactory $transactionFactory
     */
    public function __construct(
        ShipOrderInterface $shipOrder,
        OrderRepositoryInterface $orderRepository,
        ShipmentFactory $shipmentFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        Track $shipmentTrack,
        LoggerInterface $logger,
        TransactionFactory $transactionFactory,
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipOrder = $shipOrder;
        $this->logger = $logger;
        $this->shipmentTrack = $shipmentTrack;
        $this->shipmentFactory = $shipmentFactory;
        $this->transactionFactory = $transactionFactory;
        $this->supportsCustomInvoicing = $this->scopeConfig->getValue(
            'shipstation_general/shipstation/custom_invoicing',
            ScopeInterface::SCOPE_STORE
        ) == true;
        //Check for the import child items for the bundle product
        $this->importChildItemsForBundle = $this->scopeConfig->getValue(
            'shipstation_general/shipstation/import_child_products',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        // Settings to check custom/auto invoice is enabled on not
        $this->autoInvoicingEnabled = $this->scopeConfig->getValue(
            'shipstation_general/shipstation/custom_invoicing',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Settings to check mails/shipments are enabled on not
        $mailSetting = $this->scopeConfig->getValue(
            'system/smtp/disable',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $shipmentSetting = $this->scopeConfig->getValue(
            'sales_email/shipment/enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $this->mailsEnabled = $mailSetting == 0 && $shipmentSetting == 1;
    }

    /**
     * This method implements the ShipmentNotification Logic
     *
     * @return ShipmentNotificationResponse
     * @throws BadRequestException
     */
    public function executeAction(): ShipmentNotificationResponse
    {

        $request = new ShipmentNotificationRequest(json_decode($this->request->getContent(), true));
        $results = new ShipmentNotificationResponse();
        foreach ($request->notifications as $notification) {
            try {
                $order = $this->orderRepository->get($notification->order_id);
                $this->validateOrderCanShip($order);
                $quantities = $this->getOrderItemQuantities($notification->items, $order);
                $notify = $notification->notify_buyer == true;
                if ($order->canInvoice() && !$this->supportsCustomInvoicing) {
                    $invoice = $order->prepareInvoice($quantities);
                    $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                    $invoice->addComment(self::COMMENT, $notify);
                    $invoice->register();

                    $order->setIsInProcess(true);

                    $this->saveTransaction($order, $invoice);
                }

                $shipment = $this->shipmentFactory->create($order, $quantities, [[
                    'number' => (string) $notification->tracking_number,
                    'carrier_code' =>  $notification->carrier_code,
                    'title' => $notification->carrier_service_code
                ]]);

                // Internal notes are only visible to admin
                if ($notification->notes) {
                    foreach ($notification->notes as $note) {
                        if ($note->type == NoteType::InternalNotes) {
                            $shipment->addComment($note->text);
                        }
                        if ($note->type == NoteType::NotesToBuyer) {
                            $shipment->setCustomerNote($note->text);
                            $shipment->setCustomerNoteNotify($notify);
                        }
                    }
                }

                $shipment->register();

                $order->setIsInProgress(true);

                $this->_saveTransaction($order, $shipment);

                $result = new ShipmentNotificationResult();
                $result->notification_id = $notification->notification_id;
                $result->status = ShipmentNotificationStatus::Success;
                $results->notification_results[] = $result;
            } catch (Exception $exception) {
                $results->notification_results[] = new ShipmentNotificationResult([
                    'notification_id' => $notification->notification_id,
                    'succeeded' => false,
                    'failure_reason' => $exception->getMessage(),
                    'status' => 'failure'
                ]);
            }
        }
        return $results;
    }

    /**
     * This method pulls all the quantities that will be shipped
     *
     * @param ShipmentNotificationItem[] $salesOrderItems
     * @param Order $order
     * @return array
     */
    private function getOrderItemQuantities(array $salesOrderItems, Order $order): array
    {
        $quantities = [];

        foreach ($order->getItems() as $item) {
            $salesOrderItem = $this->findItemBySku($salesOrderItems, $item->getSku());
            if ($salesOrderItem) {
                $quantities[$item->getId()] = (float)$salesOrderItem->quantity;
                if ($item->getParentItemId()) {
                    $quantities[$item->getParentItemId()] = (float)$salesOrderItem->quantity;
                }
            }

            //Add child products into the shipments
            if (!$this->importChildItemsForBundle) {
                if ($item->getParentItemId()) {
                    //check for the bundle product type
                    $productType = $item->getParentItem()->getProductType();
                    if ($productType == Type::TYPE_BUNDLE) {
                        $quantities[$item->getId()] = $quantities[$item->getParentItemId()];
                    }

                }

            }

        }

        return $quantities;
    }

    /**
     * Returns a SalesOrderItem if one is available
     *
     * @param ShipmentNotificationItem[] $salesOrderItems
     * @param string $targetSku
     * @return ShipmentNotificationItem|null
     */
    private function findItemBySku(array $salesOrderItems, string $targetSku): ?ShipmentNotificationItem
    {
        foreach ($salesOrderItems as $salesOrderItem) {
            if ($salesOrderItem['sku'] === $targetSku) {
                return $salesOrderItem;
            }
        }
        return null;
    }

    /**
     * This method saves a transaction for an order
     *
     * @param Order $order
     * @param mixed $data
     * @return void
     * @throws Exception
     */
    private function saveTransaction(Order $order, mixed $data)
    {
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($data)
            ->addObject($order)
            ->save();
    }

    /**
     * Check if an order can be shipped. Return detailed errors if not
     *
     * @param Order $order
     * @return void
     * @throws BadRequestException
     */
    private function validateOrderCanShip(Order $order): void
    {
        if ($order->canUnhold() || $order->isPaymentReview()) {
            throw new BadRequestException('Order is in Payment Review state. Please check payment');
        }
        if ($order->getIsVirtual()) {
            throw new BadRequestException('Order is virtual, can\'t be shipped');
        }
        if ($order->isCanceled()) {
            throw new BadRequestException('The order has been canceled');
        }
        if ($order->getActionFlag(Order::ACTION_FLAG_SHIP) === false) {
            throw new BadRequestException('Order has already been shipped');
        }
        $canShipItem = $this->canShipAtleastOneItem($order);
        if (!$canShipItem) {
            throw new BadRequestException("All the items associated with this order cannot be shipped.
            At least one item must have all of the following to ship:
            Has quantity to ship
            Is a physical item
            Is not locked
            Has not had all of its quantity refunded");
        }
    }

    /**
     *  Makes sure that at least one item can ship for an order.
     *
     * @param Order $order
     * @return bool
     */
    private function canShipAtleastOneItem(Order $order): bool
    {
        foreach ($order->getAllItems() as $item) {
            $hasQuantityToShip = $item->getQtyToShip() > 0;
            $isPhysicalItem = !$item->getIsVirtual();
            $itemIsNotLocked = !$item->getLockedDoShip();
            $allItemsRefunded = $item->getQtyRefunded() == $item->getQtyOrdered();
            if ($hasQuantityToShip && $isPhysicalItem && $itemIsNotLocked && !$allItemsRefunded) {
                return true;
            }
        }
        return false;
    }
}
