<?php
namespace Auctane\Api\Controller\SalesOrdersExport;

use Auctane\Api\Controller\BaseController;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\Image;
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

class Index extends BaseController implements HttpPostActionInterface
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

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        Image $imageHelper,
        Message $giftMessageProvider
    ) {
        parent::__construct();
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->imageHelper = $imageHelper;
        $this->giftMessageProvider = $giftMessageProvider;
    }

    public function executeAction(): array
    {
        // Parse the request body
        $requestBody = json_decode($this->request->getContent(), true);

        // Retrieve query parameters
        $criteria = $requestBody['criteria'] ?? [];
        $fromDateTime = $criteria['from_date_time'] ?? null;
        $toDateTime = $criteria['to_date_time'] ?? null;

        $cursor = $requestBody['cursor'] ?? null;
        $pageSize = 100; // Default, you can use a request parameter if needed

        // Build search criteria with date filtering
        if ($fromDateTime) {
            $this->searchCriteriaBuilder->addFilter('updated_at', $fromDateTime, 'gteq');
        }
        if ($toDateTime) {
            $this->searchCriteriaBuilder->addFilter('updated_at', $toDateTime, 'lteq');
        }

        $this->searchCriteriaBuilder->setPageSize($pageSize);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->orderRepository->getList($searchCriteria);
        $orders = $searchResults->getItems();

        $salesOrders = [];
        foreach ($orders as $order) {
            $salesOrders[] = [
                'order_id' => $order->getEntityId(),
                'order_number' => $order->getIncrementId(),
                'status' => $order->getStatus(),
                'paid_date' => $order->getCreatedAt(),
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
            ];
        }

        $response = [
            'sales_orders' => $salesOrders,
            'cursor' => 'string' // Placeholder
        ];
        return $response;
    }

    private function getStoreDetails(OrderInterface $order): array
    {
        $store = $order->getStore();

        return [
            'name' => $store->getName(),
            'company' => $store->getConfig(Information::XML_PATH_STORE_INFO_NAME),
            'phone' => $store->getConfig(Information::XML_PATH_STORE_INFO_PHONE),
            'address_line_1' => $store->getConfig(Information::XML_PATH_STORE_INFO_STREET_LINE1),
            'address_line_2' => $store->getConfig(Information::XML_PATH_STORE_INFO_STREET_LINE2),
            'city' => $store->getConfig(Information::XML_PATH_STORE_INFO_CITY),
            'state_province' => $store->getConfig(Information::XML_PATH_STORE_INFO_REGION_CODE),
            'postal_code' => $store->getConfig(Information::XML_PATH_STORE_INFO_POSTCODE),
            'country_code' => $store->getConfig(Information::XML_PATH_STORE_INFO_COUNTRY_CODE),
            'pickup_location' => []
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
                        'quantity' => $item->getQtyOrdered(),
                        'unit_price' => floatval($item->getPrice()),
                        'taxes' => $this->getItemTaxes($item),
                        'shipping_charges' => $this->getItemShippingCharges($item),
                        'adjustments' => $this->getItemAdjustments($item),
                        'item_url' => 'string', // Placeholder
                        'modified_date_time' => $item->getUpdatedAt(),
                    ]
                ],
                'extensions' => [
                    'custom_field_1' => 'string', // Placeholder
                    'custom_field_2' => 'string', // Placeholder
                    'custom_field_3' => 'string'  // Placeholder
                ],
                'shipping_preferences' => $this->getShippingPreferences($order)
            ];
        }
        return $fulfillments;
    }

    private function getDimensions(ProductInterface $product): array|null
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

    private function mapWeightUnits(string $units): string|null
    {
        $weightUnitMapping = [
            'lbs' => 'Pound',
            'kgs' => 'Kilogram',
            'g'   => 'Gram',
            'oz'  => 'Ounce'
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
                'description' => $product->getCustomAttribute('description'),
                'identifiers' => [
                    'sku' => $product->getSku(),
                ],
                'details' => [
                    'price' => floatval($product->getPrice()),
                    'weight' => [
                        'unit' => $this->mapWeightUnits($weightUnits),
                        'value' => floatval($product->getWeight()),
                    ],
                    'dimensions' => $this->getDimensions($product),
                ],
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
            'state_province' => $address->getRegion(),
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

    private function getPaymentDetails(OrderInterface $order): array
    {
        return [
            'payment_id' => $order->getPayment()->getEntityId(),
            'payment_status' => $order->getStatus(),
            'taxes' => $this->getOrderTaxes($order),
            'shipping_charges' => $this->getOrderShippingCharges($order),
            'adjustments' => $this->getOrderAdjustments($order),
            'amount_paid' => floatval($order->getTotalPaid()),
            'coupon_code' => $order->getCouponCode(),
            'coupon_codes' => $order->getAppliedRuleIds(),
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
        $notes = [
            [
                'type' => 'NotesFromBuyer',
                'text' => $order->getCustomerNote()
            ]
        ];
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
        return [
            [
                'amount' => floatval($item->getTaxAmount()),
                'description' => 'string'
            ]
        ];
    }

    private function getItemShippingCharges(OrderItemInterface $item): array
    {
        return [
            [
                'amount' => floatval($item->getShippingAmount()),
                'description' => 'string'
            ]
        ];
    }

    private function getItemAdjustments(OrderItemInterface $item): array
    {
        return [
            [
                'amount' => floatval($item->getDiscountAmount()),
                'description' => 'Discount'
            ]
        ];
    }

    private function getOrderTaxes(OrderInterface $order): array
    {
        return [
            [
                'amount' => floatval($order->getTaxAmount()),
                'description' => 'Order Tax'
            ]
        ];
    }

    private function getOrderShippingCharges(OrderInterface $order): array
    {
        return [
            [
                'amount' => floatval($order->getShippingAmount()),
                'description' => 'Shipping Charge'
            ]
        ];
    }

    private function getOrderAdjustments(OrderInterface $order): array
    {
        return [
            [
                'amount' => floatval($order->getDiscountAmount()),
                'description' => 'Order Discount'
            ]
        ];
    }
}
