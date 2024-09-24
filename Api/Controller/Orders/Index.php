<?php
namespace Auctane\Api\Controller\Orders;

use Exception;
use Magento\Catalog\Helper\Image;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\GiftMessage\Helper\Message;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\Information;

class Index implements HttpGetActionInterface
{
    /** @var OrderRepositoryInterface  */
    protected OrderRepositoryInterface $orderRepository;
    /** @var ShipmentRepositoryInterface  */
    protected ShipmentRepositoryInterface $shipmentRepository;
    /** @var ProductRepositoryInterface */
    protected ProductRepositoryInterface $productRepository;
    /** @var JsonFactory  */
    protected JsonFactory $resultJsonFactory;
    /** @var SearchCriteriaBuilder  */
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    /** @var SortOrderBuilder  */
    protected SortOrderBuilder $sortOrderBuilder;
    /** @var RequestInterface  */
    protected RequestInterface $request;
    /** @var Image */
    protected Image $imageHelper;
    /** @var Message  */
    protected Message $giftMessageProvider;

    /**
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ProductRepositoryInterface $productRepository
     * @param JsonFactory $resultJsonFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param RequestInterface $request
     * @param Image $imageHelper
     * @param Message $giftMessageProvider
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        ShipmentRepositoryInterface $shipmentRepository,
        ProductRepositoryInterface $productRepository,
        JsonFactory $resultJsonFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        RequestInterface $request,
        Image $imageHelper,
        Message $giftMessageProvider
    ) {
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->productRepository = $productRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->request = $request;
        $this->imageHelper = $imageHelper;
        $this->giftMessageProvider = $giftMessageProvider;
    }

    /**
     * This method is executed on a GET request to /orders endpoint
     */
    public function execute()
    {
        // Retrieve query parameters
        $page = (int) $this->request->getParam('page', 1);
        $pageSize = (int) $this->request->getParam('page_size', 100);
        $lastUpdated = $this->request->getParam('last_updated', null);

        // Build search criteria with paging
        $this->searchCriteriaBuilder->setPageSize($pageSize);
        $this->searchCriteriaBuilder->setCurrentPage($page);

        // Filter by last_updated if provided
        if ($lastUpdated) {
            $this->searchCriteriaBuilder->addFilter(
                'updated_at',
                $lastUpdated,
                'gteq'
            );
        }

        // Add sorting by last updated date
        $sortOrder = $this->sortOrderBuilder
            ->setField('updated_at')
            ->setDirection('ASC')
            ->create();
        $this->searchCriteriaBuilder->addSortOrder($sortOrder);

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->orderRepository->getList($searchCriteria);
        $totalOrders = $searchResults->getTotalCount();
        $totalPages = ceil($totalOrders / $pageSize);
        $orders = $searchResults->getItems();

        $orderData = [];

        foreach ($orders as $order) {
            $order->getShippingInvoiced();
            $order->getDiscountInvoiced();
            $order->getTaxInvoiced();
            $order->getTotalInvoiced();
            $orderData[] = [
                'order_number' => $order->getIncrementId(),
                'status' => $order->getStatus(),
                'paid_date' => $order->getCreatedAt(),
                'bill_to' => $order->getBillingAddress()->getData(),
                'ship_to' => $order->getShippingAddress()->getData(),
                'store' => $this->getStoreDetails($order),
                'buyer' => [
                    'id' => $order->getCustomerId(),
                    'name' => $order->getCustomerName(),
                    'email' => $order->getCustomerEmail(),
                    'note' => $order->getCustomerNote(),
                    'tax_vat' => $order->getCustomerTaxvat(),
                ],
                // Leaving this here to allow others to enter their own mappings to
                // SEC shipping_preferences
                // https://connect.shipengine.com/orders/reference/operation/OrderSource_SalesOrdersExport/#!c=200&path=sales_orders/requested_fulfillments/shipping_preferences&t=response
                'shipping_preferences' => [],
                'payment' => $order->getPayment()->getMethod(),
                'created_date_time' => $order->getCreatedAt(),
                'modified_date_time' => $order->getUpdatedAt(),
                'shipping_method' => $order->getShippingMethod(),
                'shipping_description' => $order->getShippingDescription(),
                'items' => $this->getOrderItems($order),
                'gift_messages' => $this->getGiftOrderMessage($order),
            ];
        }

        return [
            'status' => 'success',
            'orders' => $orderData,
            'pagination' => [
                'current_page' => $page,
                'page_size' => $pageSize,
                'total_orders' => $totalOrders,
                'total_pages' => $totalPages,
                'has_more_pages' => $page < $totalPages
            ]
        ];
    }

    /**
     * This method will provide details about the store
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getStoreDetails(OrderInterface $order): array
    {
        $store = $order->getStore();
        $store->getCurrentCurrencyCode();
        $store->getFormattedAddress();
        return [
            'name' => $store->getName(),
            'currency_code' => $store->getCurrentCurrencyCode(),
            'address' => [
                'name' => $store->getConfig(Information::XML_PATH_STORE_INFO_NAME),
                'street_line1' => $store->getConfig(Information::XML_PATH_STORE_INFO_STREET_LINE1),
                'street_line2' => $store->getConfig(Information::XML_PATH_STORE_INFO_STREET_LINE2),
                'city' => $store->getConfig(Information::XML_PATH_STORE_INFO_CITY),
                'region' => $store->getConfig(Information::XML_PATH_STORE_INFO_REGION_CODE),
                'postcode' => $store->getConfig(Information::XML_PATH_STORE_INFO_POSTCODE),
                'country' => $store->getConfig(Information::XML_PATH_STORE_INFO_COUNTRY_CODE)
            ]
        ];
    }

    /**
     *  Returns product details for an item
     *
     * @param OrderItemInterface $item
     * @return array
     */
    private function getProductDetails(OrderItemInterface $item): array
    {
        try {
            $productId = $item->getProductId();
            $product = $this->productRepository->getById($item->getProductId());
            $thumbnail = $this->imageHelper->init($product, 'product_page_image_small')
                ->getUrl();
            $largeImage = $this->imageHelper->init($product, 'product_page_image_large')
                ->getUrl();

            return [
                'product_id' => $productId,
                'sku' => $product->getSku(),
                'name' => $product->getName(),
                'thumb_nail' => $thumbnail,
                'large_image' => $largeImage,
            ];
        } catch (Exception $exception) {
            return ['error' => $exception->getMessage()];
        }
    }

    /**
     * Returns the items associated with an order
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getOrderItems(OrderInterface $order): array
    {
        $items = [];
        foreach ($order->getItems() as $item) {
            $product = $this->getProductDetails($item);
            $items[] = [
                'id' => $item->getId(),
                'sku' => $item->getSku(),
                'name' => $item->getName(),
                'description' => $item->getDescription(),
                'quantity' => $item->getQtyOrdered(),
                'price' => $item->getPrice(),
                'tax_amount' => $item->getTaxAmount(),
                'weight' => $item->getWeight(),
                'is_virtual' => $item->getIsVirtual(),
                'qty_ordered' => $item->getQtyOrdered(),
                'product' => $product
            ];
        }
        return $items;
    }

    /**
     * This method should attempt to get the gift message form the order or the order items
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getGiftOrderMessage(OrderInterface $order): array
    {
        $messages = [];
        $giftId = $order->getGiftMessageId();
        if ($giftId) {
            $messages[] = [
                'id' => $giftId,
                'message' => $this->giftMessageProvider->getGiftMessage($giftId)->getMessage(),
            ];
        }
        foreach ($order->getItems() as $item) {
            $giftId = $item->getGiftMessageId();
            if ($giftId) {
                $messages[] = [
                    'id' => $giftId,
                    'message' => $this->giftMessageProvider->getGiftMessage($giftId)->getMessage()
                ];
            }
        }
        return $messages;
    }
}
