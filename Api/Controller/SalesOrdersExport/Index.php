<?php
namespace Auctane\Api\Controller\SalesOrdersExport;

use Auctane\Api\Controller\BaseController;
use Exception;
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
use Magento\Framework\Controller\Result\JsonFactory;
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
                'order_url' => $this->getOrderUrl($order),
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
                        'unit_price' => $item->getPrice(),
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

    private function getProductDetails(OrderItemInterface $item): array
    {
        try {
            $productId = $item->getProductId();
            $product = $this->productRepository->getById($productId);

            $thumbnailUrl = $this->imageHelper->init($product, 'product_page_image_small')->getUrl();
            $largeImageUrl = $this->imageHelper->init($product, 'product_page_image_large')->getUrl();

            return [
                'product_id' => $productId,
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'identifiers' => [
                    'sku' => $product->getSku(),
                    'upc' => $product->getCustomAttribute('upc') ? $product->getCustomAttribute('upc')->getValue() : null,
                    // Add other identifiers as needed
                ],
                'details' => [
                    'price' => $product->getPrice(),
                    'weight' => $product->getWeight(),
                    'dimensions' => [
                        'length' => $product->getCustomAttribute('length') ? $product->getCustomAttribute('length')->getValue() : null,
                        'width' => $product->getCustomAttribute('width') ? $product->getCustomAttribute('width')->getValue() : null,
                        'height' => $product->getCustomAttribute('height') ? $product->getCustomAttribute('height')->getValue() : null,
                    ],
                ],
                'urls' => [
                    'thumbnail' => $thumbnailUrl,
                    'large_image' => $largeImageUrl,
                    'product_url' => $product->getProductUrl(),
                ],
                'location' => 'string', // Placeholder, replace with actual data if needed
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
            'residential_indicator' => 'string', // Placeholder
            'is_verified' => true, // Placeholder
            'pickup_location' => [
                'carrier_id' => 'string', // Placeholder
                'relay_id' => 'string' // Placeholder
            ]
        ];
    }

    private function getTaxIdentifier(OrderInterface $order): array
    {
        return [
            'value' => $order->getCustomerTaxvat(),
            'type' => 'TIN' // Placeholder
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
            'amount_paid' => $order->getTotalPaid(),
            'coupon_code' => $order->getCouponCode(),
            'coupon_codes' => $order->getAppliedRuleIds(),
            'payment_method' => $order->getPayment()->getMethod(),
            'label_voucher' => [
                'url' => 'string',
                'token' => 'string'
            ],
            'prepaid_vat' => [
                [
                    'amount' => 0, // Placeholder
                    'description' => 'string' // Placeholder
                ]
            ],
            'purchase_order_number' => 'string' // Placeholder
        ];
    }

    private function getShippingPreferences(OrderInterface $order): array
    {
        return [
            'digital_fulfillment' => false,
            'additional_handling' => false,
            'bill_duties_to_sender' => false,
            'do_not_prepay_postage' => false,
            'gift' => false,
            'has_alcohol' => false,
            'insurance_requested' => false,
            'non_machinable' => false,
            'saturday_delivery' => false,
            'show_postage' => false,
            'suppress_email_notify' => false,
            'suppress_marketplace_notify' => false,
            'deliver_by_date' => 'string', // Placeholder
            'hold_until_date' => 'string', // Placeholder
            'ready_to_ship_date' => 'string', // Placeholder
            'ship_by_date' => 'string', // Placeholder
            'preplanned_fulfillment_id' => 'string', // Placeholder
            'shipping_service' => $order->getShippingDescription(),
            'package_type' => 'string', // Placeholder
            'insured_value' => $order->getShippingAmount(),
            'is_premium_program' => false,
            'premium_program_name' => 'string',
            'requested_warehouse' => 'string',
            'documents' => [
                [
                    'type' => [],
                    'data' => null,
                    'format' => null
                ]
            ]
        ];
    }

    private function getOrderNotes(OrderInterface $order): array
    {
        return [
            [
                'type' => 'string', // Placeholder
                'text' => $order->getCustomerNote()
            ]
        ];
    }

    private function getItemTaxes(OrderItemInterface $item): array
    {
        return [
            [
                'amount' => $item->getTaxAmount(),
                'description' => 'string'
            ]
        ];
    }

    private function getItemShippingCharges(OrderItemInterface $item): array
    {
        return [
            [
                'amount' => $item->getShippingAmount(),
                'description' => 'string'
            ]
        ];
    }

    private function getItemAdjustments(OrderItemInterface $item): array
    {
        return [
            [
                'amount' => $item->getDiscountAmount(),
                'description' => 'Discount'
            ]
        ];
    }

    private function getOrderTaxes(OrderInterface $order): array
    {
        return [
            [
                'amount' => $order->getTaxAmount(),
                'description' => 'Order Tax'
            ]
        ];
    }

    private function getOrderShippingCharges(OrderInterface $order): array
    {
        return [
            [
                'amount' => $order->getShippingAmount(),
                'description' => 'Shipping Charge'
            ]
        ];
    }

    private function getOrderAdjustments(OrderInterface $order): array
    {
        return [
            [
                'amount' => $order->getDiscountAmount(),
                'description' => 'Order Discount'
            ]
        ];
    }

    private function getOrderUrl(OrderInterface $order): string
    {
        return 'https://your-magento-store.com/orders/' . $order->getEntityId();
    }
}
