<?php

namespace Auctane\Api\Model\Action;

use Auctane\Api\Model\WeightAdapter;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;


/**
 * Class Export
 * @package Auctane\Api\Model\Action
 */
class Export
{
    /**
     * Exclude product type
     */
    const TYPE_CONFIGURABLE = 'configurable';

    /**
     * Set order export size
     */
    const EXPORT_SIZE = '100';

    /**
     * Order collection factory
     *
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private $orderCollectionFactory;

    /**
     * Scope config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $_scopeConfig;

    /**
     * Xml data
     *
     * @var String
     */
    private $_xmlData = '';

    /**
     * Helper
     *
     * @var \Auctane\Api\Helper\Data
     */
    private $_dataHelper;

    /**
     * Helper
     *
     * @var \Magento\GiftMessage\Helper\Message
     */
    private $giftMessageProvider;

    /**
     * Country factory
     *
     * @var \Magento\Directory\Model\CountryFactory
     */
    private $_countryFactory;

    /**
     * EAV Config instance
     *
     * @var \Magento\Eav\Model\Config
     */
    private $_eavConfig;

    /**
     * Price type
     *
     * @var boolean
     */
    private $_priceType = 0;

    /**
     * Import disocunt
     *
     * @var boolean
     */
    private $_importDiscount = 0;

    /**
     * Import child
     *
     * @var boolean
     */
    private $_importChild = 0;

    /**
     * Attributes
     *
     * @var boolean
     */
    private $_attributes = '';

    /**
     * Magento store
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

    /** @var WeightAdapter */
    private $weightAdapter;

    /**
     * Export class constructor
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $order order
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig config
     * @param \Magento\Directory\Model\CountryFactory $countryFactory country factory
     * @param \Magento\Eav\Model\Config $eavConfig config object
     * @param \Auctane\Api\Helper\Data $dataHelper helper object
     * @param \Magento\GiftMessage\Helper\Message $giftMessage The gift message.
     * @param WeightAdapter $weightAdapter
     */
    public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $order,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Auctane\Api\Helper\Data $dataHelper,
        \Magento\GiftMessage\Helper\Message $giftMessage,
        WeightAdapter $weightAdapter
    )
    {
        $this->orderCollectionFactory = $order;
        $this->_scopeConfig = $scopeConfig;
        $this->_countryFactory = $countryFactory;
        $this->_eavConfig = $eavConfig;
        $this->_dataHelper = $dataHelper;
        $this->giftMessageProvider = $giftMessage;
        //Set the configuartion variable data
        $this->_store = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        //Price export type
        $exportPrice = 'shipstation_general/shipstation/export_price';
        $this->_priceType = $this->_scopeConfig->getValue(
            $exportPrice,
            $this->_store
        );
        //Check import discount
        $importDiscount = 'shipstation_general/shipstation/import_discounts';
        $this->_importDiscount = $this->_scopeConfig->getValue(
            $importDiscount,
            $this->_store
        );
        //Check for the import child items for the bundle product
        $importChild = 'shipstation_general/shipstation/import_child_products';
        $this->_importChild = $this->_scopeConfig->getValue(
            $importChild,
            $this->_store
        );
        //Check for the import child items for the bundle product
        $attributes = 'shipstation_general/shipstation/attribute';
        $this->_attributes = $this->_scopeConfig->getValue(
            $attributes,
            $this->_store
        );

        $this->_typeBundle = Type::TYPE_BUNDLE;

        $this->weightAdapter = $weightAdapter;
    }

    /**
     * Perform an export according to the given request.
     * @param HttpRequest $request
     * @param string[] $storeIds
     * @return string
     */
    public function process(HttpRequest $request, array $storeIds): string
    {
        $from = $this->toDateString($request->getParam('start_date'));
        $to = $this->toDateString($request->getParam('end_date'));
        $page = $request->getParam('page') ?? 1;

        $this->_xmlData = "<?xml version=\"1.0\" encoding=\"utf-16\"?>\n";

        if ($from && $to) {
            $orders = $this->orderCollectionFactory->create()
                ->addAttributeToSort(OrderInterface::UPDATED_AT, SortOrder::SORT_DESC)
                ->addAttributeToFilter(OrderInterface::UPDATED_AT, ['from' => $from, 'to' => $to])
                ->addAttributeToFilter(OrderInterface::SHIPPING_DESCRIPTION, ['notnull' => true])
                ->setPage($page, self::EXPORT_SIZE);

            if (!empty($storeIds)) {
                $orders->addAttributeToFilter(OrderInterface::STORE_ID, $storeIds);
            }

            $this->writeShippableOrdersXml($orders);
        } else {
            $this->_xmlData .= "<date>date required</date>\n";
        }

        return $this->_xmlData;
    }

    /**
     * @param string|null $urlDate
     * @return string
     */
    private function toDateString(?string $urlDate): ?string
    {
        $time = strtotime(urldecode($urlDate));

        if (!$time) {
            return null;
        }

        return date('Y-m-d H:i:s', $time);
    }

    /**
     * @param Collection $orders
     * @return $this
     */
    private function writeShippableOrdersXml(Collection $orders): self
    {
        $this->_xmlData .= "<Orders pages=\"{$orders->getLastPageNumber()}\">\n";

        /** @var Order $order */
        foreach ($orders as $order) {
            $this->writeOrderXml($order);
        }

        $this->_xmlData .= "</Orders>";

        return $this;
    }

    /**
     * Write the order in xml file
     * @param Order $order
     * @return $this
     */
    private function writeOrderXml(Order $order): self
    {
        $this->_xmlData .= "\t<Order>\n";
        $this->addXmlElement("OrderNumber", $order->getIncrementId());
        $this->addXmlElement("OrderDate", $order->getCreatedAt());
        $this->addXmlElement("OrderStatus", $order->getStatus());
        $this->addXmlElement("LastModified", $order->getUpdatedAt());
        $this->addXmlElement("CurrencyCode", $order->getOrderCurrencyCode());

        $this->addXmlElement(
            "ShippingMethod",
            "{$order->getShippingDescription()}|{$order->getShippingMethod()}"
        );

        if ($this->_priceType) {
            $orderTotal = $order->getBaseGrandTotal();
            $orderTax = $order->getBaseTaxAmount();
            $orderShipping = $order->getBaseShippingAmount();
        } else {
            $orderTotal = $order->getGrandTotal();
            $orderTax = $order->getTaxAmount();
            $orderShipping = $order->getShippingAmount();
        }

        $this->addXmlElement("OrderTotal", $orderTotal);
        $this->addXmlElement("TaxAmount", $orderTax);
        $this->addXmlElement("ShippingAmount", $orderShipping);
        $this->addXmlElement("InternalNotes", "<![CDATA[{$order->getCustomerNote()}]]>");
        $this->addXmlElement("StoreCode", $order->getStore()->getCode());

        $this->_getGiftMessageInfo($order);

        $this->_xmlData .= "\t<Customer>\n";
        $this->addXmlElement("CustomerCode", $order->getCustomerEmail());
        $this->_getBillingInfo($order); //call to the billing info function
        $this->_getShippingInfo($order); //call to the shipping info function
        $this->_xmlData .= "\t</Customer>\n";
        $this->_xmlData .= "\t<Items>\n";
        $this->_orderItem($order); //call to the order items function
        //Get the order discounts
        if ($this->_importDiscount && $order->getDiscountAmount() != '0.0000') {
            $this->_getOrderDiscounts($order);
        }

        $this->_xmlData .= "\t</Items>\n";
        $this->_xmlData .= "\t</Order>\n";

        return $this;
    }

    /**
     * Function to add field to xml.
     *
     * @param string $strFieldName
     * @param string $strValue
     * @return $this
     */
    private function addXmlElement(string $strFieldName, string $strValue): self
    {
        $strResult = mb_convert_encoding(
            str_replace('&', '&amp;', $strValue),
            'UTF-8'
        );
        $this->_xmlData .= "\t\t<$strFieldName>$strResult</$strFieldName>\n";

        return $this;
    }

    /**
     * Get the Gift information of order or item.
     * @param Order|Order\Item $messageContainer
     * @return $this
     */
    private function _getGiftMessageInfo($messageContainer): self
    {
        if ($giftId = $messageContainer->getGiftMessageId()) {
            $gift = $this->giftMessageProvider->getGiftMessage($giftId);
            $this->addXmlElement("GiftMessage", "<![CDATA[From: {$gift->getSender()}\nTo: {$gift->getRecipient()}\nMessage: {$gift->getMessage()}]]>");
        }

        $this->addXmlElement("Gift", !is_null($giftId));

        return $this;
    }

    /**
     * Get the Billing information of order
     *
     * @param Order $order billing information
     *
     * @return billing information
     */
    private function _getBillingInfo($order)
    {
        $billing = $order->getBillingAddress();
        if (!empty($billing)) {
            $name = $billing->getFirstname() . ' ' . $billing->getLastname();
            $this->_xmlData .= "\t<BillTo>\n";
            $this->addXmlElement("Name", '<![CDATA[' . $name . ']]>');
            $this->addXmlElement(
                "Company",
                '<![CDATA[' . $billing->getCompany() . ']]>'
            );
            $this->addXmlElement("Phone", $billing->getTelephone());
            $this->addXmlElement("Email", $order->getCustomerEmail());
            $this->_xmlData .= "\t</BillTo>\n";
        }
    }

    /**
     * Get the Shipping information of order
     *
     * @param Order $order get shipping information
     *
     * @return Shipping information
     */
    private function _getShippingInfo($order)
    {
        $shipping = $order->getShippingAddress();
        if (!empty($shipping)) {
            $name = $shipping->getFirstname() . ' ' . $shipping->getLastname();

            $country = '';
            if ($shipping->getCountryId()) {
                $country = $this->_countryFactory->create()
                    ->loadByCode($shipping->getCountryId())->getName();
            }

            $this->_xmlData .= "\t<ShipTo>\n";
            $this->addXmlElement("Name", '<![CDATA[' . $name . ']]>');
            $this->addXmlElement(
                "Company",
                '<![CDATA[' . $shipping->getCompany() . ']]>'
            );
            $this->addXmlElement(
                "Address1",
                '<![CDATA[' . $shipping->getStreetLine(1) . ']]>'
            );
            $this->addXmlElement(
                "Address2",
                '<![CDATA[' . $shipping->getStreetLine(2) . ']]>'
            );
            $this->addXmlElement(
                "City",
                '<![CDATA[' . $shipping->getCity() . ']]>'
            );
            $this->addXmlElement(
                "State",
                '<![CDATA[' . $shipping->getRegion() . ']]>'
            );
            $this->addXmlElement("PostalCode", $shipping->getPostcode());
            $this->addXmlElement("Country", '<![CDATA[' . $country . ']]>');
            $this->addXmlElement("Phone", $shipping->getTelephone());
            $this->_xmlData .= "\t</ShipTo>\n";
        }
    }

    /**
     * Write the order item in xml response data
     *
     * @param Order $order
     * @return $this
     * @throws LocalizedException
     */
    private function _orderItem(Order $order): self
    {
        $imageUrl = '';

        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getProductType() == self::TYPE_CONFIGURABLE) {
                continue;
            }

            if ($orderItem->getIsVirtual()) {
                continue;
            }

            if ($this->_priceType) {
                $price = $orderItem->getBasePrice();
            } else {
                $price = $orderItem->getPrice();
            }

            $foreighWeight = $this->weightAdapter->toForeignWeight($orderItem->getWeight());
            $name = $orderItem->getName();
            $product = $orderItem->getProduct();

            if ($product) {
                $attribute = $product->getResource()->getAttribute('small_image');
                $imageUrl = $attribute->getFrontend()->getUrl($orderItem->getProduct());
            }

            $parentItem = $orderItem->getParentItem();

            if ($parentItem) {
                if ($parentItem->getProductType() == Type::TYPE_BUNDLE) {
                    //Remove child items from the response data
                    if (!$this->_importChild) {
                        continue;
                    }

                    $price = 0;
                    $foreighWeight = $this->weightAdapter->toForeignWeight(0);
                }

                //set the item price from parent item price
                if ($parentItem->getProductType() == Configurable::TYPE_CODE) {
                    if ($price == '0.0000' || $price == null) {
                        $price = $this->_extractPriceFromParentItem($parentItem);
                    }

                    $name = $parentItem->getName();
                }

                // Set the parent image url if the item image is not set.
                $parentProduct = $parentItem->getProduct();
                if (!$imageUrl && !empty($parentProduct)) {
                    $attribute = $parentProduct->getResource()->getAttribute('small_image');
                    $imageUrl = $attribute->getFrontend()->getUrl($parentItem->getProduct());
                }
            }

            $this->_xmlData .= "\t<Item>\n";

            $this->addXmlElement("SKU", $orderItem->getSku());
            $this->addXmlElement("Name", '<![CDATA[' . $name . ']]>');
            $this->addXmlElement("ImageUrl", $imageUrl);
            $this->addXmlElement("Weight", $foreighWeight->getValue());
            $this->addXmlElement("WeightUnits", $foreighWeight->getUnit());
            $this->addXmlElement("UnitPrice", $price);
            $this->addXmlElement("Quantity", (int)$orderItem->getQtyOrdered());

            $this->_getGiftMessageInfo($orderItem);
            /*
             * Check for the attributes
             */
            $this->_xmlData .= "\t<Options>\n";
            $attributeCodes = explode(',', $this->_attributes);
            $this->_writeOrderItemAttributesAsOptions($attributeCodes, $orderItem);

            //custom attribute selection.
            $this->_getCustomAttributes($orderItem);
            $this->_xmlData .= "\t</Options>\n";
            $this->_xmlData .= "\t</Item>\n";
        }

        return $this;
    }

    /**
     * @param OrderItemInterface $parentItem
     * @return float|null
     */
    private function _extractPriceFromParentItem(OrderItemInterface $parentItem)
    {
        return $this->_priceType
            ? $parentItem->getBasePrice()
            : $parentItem->getPrice();
    }

    /**
     * @param array $attributeCodes
     * @param OrderItemInterface $orderItem
     * @return array
     * @throws LocalizedException
     */
    private function _writeOrderItemAttributesAsOptions(array $attributeCodes, OrderItemInterface $orderItem)
    {
        foreach ($attributeCodes as $attributeCode) {
            $product = $orderItem->getProduct();
            $data = '';
            if (!empty($product)) {
                $data = $orderItem->getProduct()
                    ->hasData($attributeCode);
            }

            if ($attributeCode && $data) {
                $attribute = $this->_eavConfig->getAttribute(
                    $this->_getEntityType(),
                    $attributeCode
                );
                $name = $attribute->getFrontendLabel();
                $inputType = $attribute->getFrontendInput();
                if (in_array($inputType, ['select', 'multiselect'])) {
                    $value = $orderItem->getProduct()
                        ->getAttributeText($attributeCode);
                } else {
                    $value = $orderItem->getProduct()
                        ->getData($attributeCode);
                }

                //Add option to xml data
                if ($value) {
                    $this->_writeOption($name, $value);
                }
            }
        }
    }

    /**
     * Retrieve entity type
     *
     * @return string
     */
    private function _getEntityType()
    {
        return \Magento\Catalog\Model\Product::ENTITY;
    }

    /**
     * Write the order discount details in to the xml response data
     *
     * @param string $label XML Node
     * @param string $value XML Value
     *
     * @return Xml
     */
    private function _writeOption($label, $value)
    {
        $this->_xmlData .= "\t<Option>\n";
        $this->addXmlElement("Name", '<![CDATA[' . $label . ']]>');
        if (is_array($value)) {
            $value = implode(', ', $value);
        }

        $this->addXmlElement("Value", '<![CDATA[' . $value . ']]>');
        $this->_xmlData .= "\t</Option>\n";
    }

    /**
     * Get the custom product attributes.
     * @param object $item order item
     *
     * @return $item options
     */
    private function _getCustomAttributes($item)
    {
        // Get product options
        $options = $item->getProductOptionByCode('options');
        if (!empty($options)) {
            foreach ($options as $option) {
                $this->_writeOption($option['label'], $option['value']);
            }
        }

        $buyRequest = $item->getProductOptionByCode('info_buyRequest');
        if ($buyRequest && isset($buyRequest['super_attribute'])) {
            // super_attribute is non-null and non-empty
            // there must be a Configurable involved
            $parentItem = $item->getParentItem();
            if (!empty($parentItem)) {
                // export configurable custom options as they are stored in parent
                $parentOptions = $parentItem->getProductOptionByCode('options');
                if (!empty($parentOptions)) {
                    foreach ($parentOptions as $option) {
                        $this->_writeOption($option['label'], $option['value']);
                    }
                }
            }
        }
    }

    /**
     * Write the order discount details in to the xml response data
     *
     * @param Order $order order object
     *
     * @return void
     */
    private function _getOrderDiscounts($order)
    {
        if ($order->getCouponCode()) {
            $code = $order->getCouponCode();
        } else {
            $code = 'AUTOMATIC_DISCOUNT';
        }

        $this->_xmlData .= "\t<Item>\n";
        $this->addXmlElement("SKU", $code);
        $this->addXmlElement("Name", '');
        $this->addXmlElement("Adjustment", 'true');
        $this->addXmlElement("Quantity", 1);
        $this->addXmlElement("UnitPrice", $order->getDiscountAmount());
        $this->_xmlData .= "\t</Item>\n";
    }
}
