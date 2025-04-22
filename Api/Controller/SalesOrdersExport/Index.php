<?php
namespace Auctane\Api\Controller\SalesOrdersExport;

use Auctane\Api\Controller\BaseAuthorizedController;
use Auctane\Api\Controller\BaseController;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Model\OrderSourceAPI\Models\PaymentStatus;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrder;
use Auctane\Api\Model\OrderSourceAPI\Models\SalesOrderStatus;
use Auctane\Api\Model\OrderSourceAPI\Models\WeightUnit;
use Auctane\Api\Model\OrderSourceAPI\Requests\SalesOrdersExportRequest;
use Auctane\Api\Model\OrderSourceAPI\Responses\SalesOrdersExportResponse;
use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\GiftMessage\Helper\Message;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Store\Model\Information;

class Index extends BaseAuthorizedController implements HttpPostActionInterface
{
    /** @var OrderRepositoryInterface */
    protected OrderRepositoryInterface $orderRepository;
    /** @var ShipmentRepositoryInterface */
    protected ShipmentRepositoryInterface $shipmentRepository;
    /** @var ProductRepositoryInterface */
    protected ProductRepositoryInterface $productRepository;
    /** @var SearchCriteriaBuilder */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    /** @var SortOrderBuilder */
    protected SortOrderBuilder $sortOrderBuilder;
    /** @var Image */
    protected Image $imageHelper;
    /** @var Message */
    protected Message $giftMessageProvider;
    /** @var SalesOrdersExportRequest  */
    protected SalesOrdersExportRequest $salesOrdersExportRequest;
    /** @var CollectionFactory  */
    protected CollectionFactory $regionCollection;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        Image $imageHelper,
        Message $giftMessageProvider,
        CollectionFactory $regionCollection,
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->imageHelper = $imageHelper;
        $this->giftMessageProvider = $giftMessageProvider;
        $this->regionCollection = $regionCollection;
    }

    /**
     * This method implements the SalesOrdersExport Logic found
     * https://connect.shipengine.com/orders/reference/operation/OrderSource_SalesOrdersExport/
     *
     * @return SalesOrdersExportResponse
     * @throws BadRequestException
     */
    public function executeAction(): SalesOrdersExportResponse
    {
        // Parse the request body
        $this->salesOrdersExportRequest = new SalesOrdersExportRequest(json_decode($this->request->getContent(), true));
        // Retrieve query parameters
        $fromDateTime = $this->salesOrdersExportRequest->criteria?->from_date_time ?? null;
        $toDateTime = $this->salesOrdersExportRequest->criteria?->to_date_time ?? null;

        // Build search criteria with date filtering
        if ($fromDateTime) {
            $this->searchCriteriaBuilder->addFilter('updated_at', $fromDateTime, 'gteq');
        }
        if ($toDateTime) {
            $this->searchCriteriaBuilder->addFilter('updated_at', $toDateTime, 'lteq');
        }

        $cursor = $this->getCursor();
        $currentPage = $cursor['page'];
        $pageSize = $cursor['page_size'];

        $this->searchCriteriaBuilder->setPageSize($pageSize)->setCurrentPage($currentPage);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->orderRepository->getList($searchCriteria);
        $totalCount = $searchResults->getTotalCount();
        $totalPages = ceil($totalCount / $cursor['page_size']);
        $hasMorePages = $totalPages > $currentPage;

        if ($totalCount == 0 || $currentPage > $totalPages || $currentPage <= 0) {
            $response = new SalesOrdersExportResponse();
            $response->sales_orders = [];
            return $response;
        }

        $orders = $searchResults->getItems();

        $salesOrders = [];
        foreach ($orders as $order) {
            $salesOrders[] = new SalesOrder([
                'order_id' => $order->getEntityId(),
                'order_number' => $order->getIncrementId(),
                'status' => $this->getOrderStatus($order),
                'paid_date' => $this->getOrderPaymentDate($order),
                'requested_fulfillments' => $this->getRequestedFulfillments($order),
                'buyer' => $this->getBuyerDetails($order),
                'bill_to' => $this->getAddressDetails($order->getBillingAddress()),
                'currency' => $order->getOrderCurrencyCode(),
                'tax_identifier' => $this->getTaxIdentifier($order),
                'payment' => $this->getPaymentDetails($order),
                'ship_from' => $this->getStoreDetails($order),
                'notes' => $this->getOrderNotes($order),
                'created_date_time' => $order->getCreatedAt(),
                'modified_date_time' => $order->getUpdatedAt(),
            ]);
        }

        $response = new SalesOrdersExportResponse();
        $response->sales_orders = $salesOrders;
        if ($hasMorePages) {
            $response->cursor = $this->getNextCursor($currentPage + 1, $pageSize, $totalPages, $totalCount);
        }
        return $response;
    }

    /**
     * This method attempts to get the cursor or a default
     *
     * @return array
     * @throws BadRequestException
     */
    private function getCursor(): array
    {
        $cursor = $this->salesOrdersExportRequest->cursor ?? null;
        if (!is_string($cursor) || empty($cursor)) {
            return [
                'page' => 1,
                'page_size' => 100,
            ];
        }
        $cursorData = json_decode($cursor);
        if (!is_numeric($cursorData->page)) {
            throw new BadRequestException('cursor page "' . $cursorData->page . '" is invalid');
        }
        if (!is_numeric($cursorData->page_size)) {
            throw new BadRequestException('cursor page_size "' . $cursorData->page_size . '" is invalid');
        }
        return [
            'page' => $cursorData->page,
            'page_size' => $cursorData->page_size,
        ];
    }

    /**
     * This method returns the serialized
     *
     * @param int $currentPage
     * @param int $pageSize
     * @param int $totalPages
     * @param int $totalOrders
     * @return string
     */
    private function getNextCursor(int $currentPage, int $pageSize, int $totalPages, int $totalOrders): string
    {
        return json_encode([
            'page' => $currentPage,
            'page_size' => $pageSize,
            'total_pages' => $totalPages,
            'total_orders' => $totalOrders,
        ]);
    }

    private function getOrderPaymentDate(OrderInterface $order): string|null
    {
        $invoices = $order->getInvoiceCollection();
        foreach ($invoices as $invoice) {
            return $invoice->getCreatedAt();
        }
        return null;
    }

    private function getStoreDetails(OrderInterface $order): array
    {
        $store = $order->getStore();
        $regionId = $store->getConfig(Information::XML_PATH_STORE_INFO_REGION_CODE);
        try {
            $regionCollection = $this->regionCollection->create();
            $region = $regionCollection->getItemById($regionId);
        } catch (Exception) {
            $region = null;
        }
        return [
            'name' => $store->getName(),
            'company' => $store->getConfig(Information::XML_PATH_STORE_INFO_NAME),
            'phone' => $store->getConfig(Information::XML_PATH_STORE_INFO_PHONE),
            'address_line_1' => $store->getConfig(Information::XML_PATH_STORE_INFO_STREET_LINE1),
            'address_line_2' => $store->getConfig(Information::XML_PATH_STORE_INFO_STREET_LINE2),
            'city' => $store->getConfig(Information::XML_PATH_STORE_INFO_CITY),
            'state_province' => $region?->getData('code'),
            'postal_code' => $store->getConfig(Information::XML_PATH_STORE_INFO_POSTCODE),
            'country_code' => $store->getConfig(Information::XML_PATH_STORE_INFO_COUNTRY_CODE),
        ];
    }

    private function getRequestedFulfillments(OrderInterface $order): array
    {
        $fulfillments = [];
        foreach ($order->getItems() as $item) {
            $fulfillments[] = [
                'requested_fulfillment_id' => $item->getItemId(),
                'ship_to' => $this->getAddressDetails($order->getShippingAddress()),
                'items' => [
                    [
                        'line_item_id' => $item->getItemId(),
                        'description' => $item->getName(),
                        'product' => $this->getProductDetails($item),
                        'quantity' => (int)$item->getQtyOrdered(),
                        'unit_price' => floatval($item->getPrice()),
                        'taxes' => $this->getItemTaxes($item),
                        'shipping_charges' => $this->getItemShippingCharges($item),
                        'adjustments' => $this->getItemAdjustments($item),
                        'modified_date_time' => $item->getUpdatedAt(),
                    ]
                ],
                'shipping_preferences' => $this->getShippingPreferences($order)
            ];
        }
        return $fulfillments;
    }

    private function getDimensions(OrderItemInterface $product): array|null
    {
        $length = $product->getCustomAttribute('length')?->getValue();
        $width = $product->getCustomAttribute('width')?->getValue();
        $height = $product->getCustomAttribute('height')?->getValue();
        if (!$length || !$width || !$height) {
            return null;
        }
        return [
            'length' => $length,
            'width' => $width,
            'height' => $height,
        ];
    }

    private function mapWeightUnits(string $units): WeightUnit | null
    {
        $weightUnitMapping = [
            'lbs' => WeightUnit::POUND,
            'kgs' => WeightUnit::KILOGRAM,
            'g'   => WeightUnit::GRAM,
            'oz'  => WeightUnit::OUNCE,
        ];
        return $weightUnitMapping[$units] ?? null;
    }
    private function getProductDetails(OrderItemInterface $item): array
    {
        try {
            $productId = $item->getProductId();
            $product = $this->productRepository->getById($productId);

            $thumbnailUrl = $this->imageHelper->init($product, 'product_page_image_small')->getUrl();
            $largeImageUrl = $this->imageHelper->init($product, 'product_page_image_large')->getUrl();
            $weightUnits = $this->scopeConfig->getValue(
                'general/locale/weight_unit',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            return [
                'product_id' => $productId,
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'identifiers' => [
                    'sku' => $product->getSku(),
                ],
                'price' => floatval($product->getPrice()),
                'weight' => [
                    'unit' => $this->mapWeightUnits($weightUnits),
                    'value' => floatval($item->getWeight()),
                ],
                'dimensions' => $this->getDimensions($item),
                'urls' => [
                    'thumbnail_url' => $thumbnailUrl,
                    'image_url' => $largeImageUrl,
                    'product_url' => $product->getProductUrl(),
                ],
            ];
        } catch (Exception $exception) {
            return ['error' => $exception->getMessage()];
        }
    }

    private function getBuyerDetails(OrderInterface $order): array
    {
        return [
            'buyer_id' => $order->getCustomerId(),
            'name' => $order->getCustomerName(),
            'email' => $order->getCustomerEmail(),
            'phone' => $order->getBillingAddress()->getTelephone()
        ];
    }

    private function getAddressDetails($address): array
    {
        if (!$address) {
            return [];
        }

        return [
            'name' => $address->getFirstname() . ' ' . $address->getLastname(),
            'company' => $address->getCompany(),
            'phone' => $address->getTelephone(),
            'address_line_1' => $address->getStreetLine(1),
            'address_line_2' => $address->getStreetLine(2),
            'address_line_3' => $address->getStreetLine(3),
            'city' => $address->getCity(),
            'state_province' => $address->getRegionCode(),
            'postal_code' => $address->getPostcode(),
            'country_code' => $address->getCountryId(),
        ];
    }

    private function getTaxIdentifier(OrderInterface $order): array|null
    {
        if (!$order->getCustomerTaxvat()) {
            return null;
        }
        return [
            'value' => $order->getCustomerTaxvat(),
            'type' => 'VAT'
        ];
    }

    /**
     * This attempts to map the order status
     *
     * @param OrderInterface $order
     * @return SalesOrderStatus
     */
    private function getOrderStatus(OrderInterface $order): SalesOrderStatus
    {
        $status = $order->getStatus();
        $userMappings = $this->salesOrdersExportRequest->sales_order_status_mappings ?? [];
        $defaultMappings = [
            'pending' => SalesOrderStatus::AwaitingPayment,
            'pending_payment' => SalesOrderStatus::AwaitingPayment,
            'processing' => SalesOrderStatus::PendingFulfillment,
            'complete' => SalesOrderStatus::Completed,
            'closed' => SalesOrderStatus::Cancelled,
            'canceled' => SalesOrderStatus::Cancelled,
            'holded' => SalesOrderStatus::OnHold,
            'payment_review' => SalesOrderStatus::AwaitingPayment,
            'fraud' => SalesOrderStatus::Cancelled,
        ];
        $mappings = array_merge($defaultMappings, $userMappings);
        return $mappings[$status] ?? SalesOrderStatus::OnHold;
    }
    private function getPaymentDetails(OrderInterface $order): array
    {
        $paymentStatusMapping = [
            'pending' => PaymentStatus::AwaitingPayment,
            'pending_payment' => PaymentStatus::AwaitingPayment,
            'processing' => PaymentStatus::PaymentInProcess,
            'complete' => PaymentStatus::Paid,
            'closed' => PaymentStatus::PaymentCancelled,
            'canceled' => PaymentStatus::PaymentCancelled,
            'holded' => PaymentStatus::AwaitingPayment,
            'payment_review' => PaymentStatus::PaymentInProcess,
            'fraud' => PaymentStatus::PaymentFailed
        ];
        return [
            'payment_id' => $order->getPayment()->getEntityId(),
            'payment_status' => $paymentStatusMapping[$order->getStatus()] ?? "Other",
            'taxes' => $this->getOrderTaxes($order),
            'shipping_charges' => $this->getOrderShippingCharges($order),
            'adjustments' => $this->getOrderAdjustments($order),
            'amount_paid' => floatval($order->getTotalPaid()),
            'coupon_code' => $order->getCouponCode(),
            'payment_method' => $order->getPayment()->getMethod(),
        ];
    }

    private function getShippingPreferences(OrderInterface $order): array
    {
        return [
            'digital_fulfillment' => $this->getIsDigitalFulfillment($order),
            'gift' => $order->getGiftMessageId() !== null,
            'shipping_service' => $order->getShippingDescription(),
        ];
    }

    private function getIsDigitalFulfillment(OrderInterface $order): bool
    {
        $isDigital = true;
        foreach ($order->getItems() as $item) {
            $productType = $item->getProductType();
            // Check if the product type is not digital (e.g., simple, configurable, bundle)
            if ($productType !== 'virtual' && $productType !== 'downloadable') {
                $isDigital = false;
                break;
            }
        }
        return $isDigital;
    }

    private function getOrderNotes(OrderInterface $order): array
    {
        $notes = [];
        if ($order->getCustomerNote()) {
            $notes[] = [
                'type' => 'NotesFromBuyer',
                'text' => $order->getCustomerNote()
            ];
        }
        foreach ($order->getStatusHistoryCollection() as $history) {
            $comment = $history->getComment();
            if ($comment) {
                $notes[] = [
                    'type' => 'InternalNotes',
                    'text' => $comment
                ];
            }
        }
        $giftMessageId = $order->getGiftMessageId();
        if ($giftMessageId) {
            $giftMessage = $this->giftMessageProvider->getGiftMessage($giftMessageId);
            $notes[] = [
                'type' => 'GiftMessage',
                'text' => $giftMessage->getMessage(),
            ];
        }
        return $notes;
    }

    private function getItemTaxes(OrderItemInterface $item): array
    {
        if (!$item->getTaxAmount()) {
            return [];
        }
        return [
            [
                'amount' => floatval($item->getTaxAmount()),
                'description' => 'Taxed Amount'
            ]
        ];
    }

    private function getItemShippingCharges(OrderItemInterface $item): array
    {
        if (!$item->getShippingAmount()) {
            return [];
        }
        return [
            [
                'amount' => floatval($item->getShippingAmount()),
                'description' => 'Shipping Amount'
            ]
        ];
    }

    private function getItemAdjustments(OrderItemInterface $item): array
    {
        if (!$item->getDiscountAmount() || $item->getDiscountAmount() == 0) {
            return [];
        }
        return [
            [
                'amount' => floatval($item->getDiscountAmount()),
                'description' => 'Discount Amount'
            ]
        ];
    }

    private function getOrderTaxes(OrderInterface $order): array
    {
        if (!$order->getTaxAmount()) {
            return [];
        }
        return [
            [
                'amount' => floatval($order->getTaxAmount()),
                'description' => 'Order Tax'
            ]
        ];
    }

    private function getOrderShippingCharges(OrderInterface $order): array
    {
        if (!$order->getShippingAmount()) {
            return [];
        }
        return [
            [
                'amount' => floatval($order->getShippingAmount()),
                'description' => 'Shipping Charge'
            ]
        ];
    }

    private function getOrderAdjustments(OrderInterface $order): array
    {
        if (!$order->getDiscountAmount() || $order->getDiscountAmount() === 0) {
            return [];
        }
        return [
            [
                'amount' => floatval($order->getDiscountAmount()),
                'description' => 'Order Discount'
            ]
        ];
    }
}
