<?php

namespace Auctane\Api\Model\Action;

use Auctane\Api\Helper\Data;
use Auctane\Api\Model\Config\Source\ImportChild;
use Auctane\Api\Model\WeightAdapter;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\GiftMessage\Helper\Message;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;


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
     * Scope config
     *
     * @var ScopeConfigInterface
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
     * @var Data
     */
    private $_dataHelper;

    /**
     * Helper
     *
     * @var Message
     */
    private $giftMessageProvider;

    /**
     * EAV Config instance
     *
     * @var Config
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

    /** @var WeightAdapter */
    private $weightAdapter;

    /** @var CollectionFactory */
    private $orderCollectionFactory;
    /** @var RegionCollectionFactory */
    private $regionCollectionFactory;

    /** @var LoggerInterface */
    private $logger;

    /**
     * Export class constructor
     *
     * @param CollectionFactory $orderCollectionFactory order
     * @param ScopeConfigInterface $scopeConfig config
     * @param Config $eavConfig config object
     * @param Data $dataHelper helper object
     * @param Message $giftMessage The gift message.
     * @param WeightAdapter $weightAdapter
     * @param RegionCollectionFactory $regionCollectionFactory
     */
    public function __construct(
        CollectionFactory $orderCollectionFactory,
        ScopeConfigInterface $scopeConfig,
        Config $eavConfig,
        Data $dataHelper,
        Message $giftMessage,
        WeightAdapter $weightAdapter,
        RegionCollectionFactory $regionCollectionFactory,
        LoggerInterface $logger
    )
    {
        $this->_scopeConfig = $scopeConfig;
        $this->_eavConfig = $eavConfig;
        $this->_dataHelper = $dataHelper;
        $this->giftMessageProvider = $giftMessage;
        $this->weightAdapter = $weightAdapter;

        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->regionCollectionFactory = $regionCollectionFactory;
        $this->logger = $logger;

        // @todo Initialisation in constructor is forbidden. Move to Config object.
        //Price export type
        $exportPrice = 'shipstation_general/shipstation/export_price';
        $this->_priceType = $this->_scopeConfig->getValue($exportPrice, ScopeInterface::SCOPE_STORE);
        //Check import discount
        $importDiscount = 'shipstation_general/shipstation/import_discounts';
        $this->_importDiscount = $this->_scopeConfig->getValue($importDiscount, ScopeInterface::SCOPE_STORE);
        //Check for the import child items for the bundle product
        $importChild = 'shipstation_general/shipstation/import_child_products';
        $this->_importChild = $this->_scopeConfig->getValue($importChild, ScopeInterface::SCOPE_STORE);
        //Check for the import child items for the bundle product
        $attributes = 'shipstation_general/shipstation/attribute';
        $this->_attributes = $this->_scopeConfig->getValue($attributes, ScopeInterface::SCOPE_STORE);
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
        $this->addXmlElement("OrderNumber", "<![CDATA[{$order->getIncrementId()}]]>");
        $this->addXmlElement("OrderDate", "<![CDATA[{$order->getCreatedAt()}]]>");
        $this->addXmlElement("OrderStatus", "<![CDATA[{$order->getStatus()}]]>");
        $this->addXmlElement("LastModified", "<![CDATA[{$order->getUpdatedAt()}]]>");
        $this->addXmlElement("CurrencyCode", "<![CDATA[{$order->getOrderCurrencyCode()}]]>");

        $this->addXmlElement(
            "ShippingMethod",
            "<![CDATA[{$order->getShippingDescription()}|{$order->getShippingMethod()}]]>"
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

        $this->addXmlElement("OrderTotal", "<![CDATA[{$orderTotal}]]>");
        $this->addXmlElement("TaxAmount", "<![CDATA[{$orderTax}]]>");
        $this->addXmlElement("ShippingAmount", "<![CDATA[{$orderShipping}]]>");
        $this->_getInternalNotes($order);
        $this->addXmlElement("StoreCode", "<![CDATA[{$order->getStore()->getCode()}]]>");

        if ($order->getGiftMessageId())
		{
			$this->_getGiftMessageInfo($order);
		} 
		else 
		{
			$item = null;
			foreach ($order->getItems() as $orderItem) 
			{
				if ($orderItem->getGiftMessageId()) 
				{
					$item = $orderItem;
					break;
				}
			}
	
			if ($item) {
				$this->_getGiftMessageInfo($item);
			} else {
				$this->_getGiftMessageInfo($order);
			}
		}

        $this->_xmlData .= "\t<Customer>\n";
        $this->addXmlElement("CustomerCode", "<![CDATA[{$order->getCustomerEmail()}]]>");
        $this->_getBillingInfo($order); //call to the billing info function

        if ($shipping = $order->getShippingAddress()) {
            $this->_getShippingInfo($shipping);
        }

        $this->_xmlData .= "\t</Customer>\n";
        $this->_xmlData .= "\t<Items>\n";
        $this->_orderItem($order); //call to the order items function
        //Get the order discounts
        if ($this->_importDiscount && !is_null($order->getDiscountAmount()) && $order->getDiscountAmount() != '0.0000') {
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
     * Write All Internal notes into the xml response data
     * @param Order $order
     * @return void
     */
    private function _getInternalNotes(Order $order)
    {
        $internalNotes = array();
        foreach ($order->getStatusHistoryCollection() as $internalNote) {
            if (empty(trim($internalNote->getComment() ?? ""))) continue; // You can no longer trim a null string in PHP8.
            array_unshift($internalNotes, $internalNote->getComment());
        }
        $internalNotes = implode("\n", $internalNotes);
        $this->addXmlElement("InternalNotes", "<![CDATA[{$internalNotes}]]>");
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

        $this->addXmlElement("Gift", !is_null($giftId) ? 'true' : 'false');

        return $this;
    }

    /**
     * Get the Billing information of order
     *
     * @param Order $order
     * @return $this
     */
    private function _getBillingInfo(Order $order): self
    {
        $billing = $order->getBillingAddress();

        if (is_null($billing)) {
            return $this;
        }

        $this->_xmlData .= "\t<BillTo>\n";
        $this->addXmlElement("Name", "<![CDATA[{$billing->getFirstname()} {$billing->getLastname()}]]>");
        $this->addXmlElement("Company", "<![CDATA[{$billing->getCompany()}]]>");
        $this->addXmlElement("Phone", "<![CDATA[{$billing->getTelephone()}]]>");
        $this->addXmlElement("Email", "<![CDATA[{$order->getCustomerEmail()}]]>");
        $this->_xmlData .= "\t</BillTo>\n";

        return $this;
    }

    /**
     * @param string $regionName
     * @return Region
     */
    private function getRegion(string $regionName): Region
    {
        /** @var RegionCollection $regionCollection */
        $regionCollection = $this->regionCollectionFactory->create();
        $regionCollection->addRegionNameFilter($regionName);
        /** @var Region $region */
        $region = $regionCollection->getFirstItem();

        return $region;
    }

    /**
     * Limit the number of chars for a variable.
     *
     * @param string $value
     * @param int $maxLength
     * @return string
     */
    private function trimChars(string $value = null, int $maxLength): string
    {   
        if (strlen($value ?? "") > $maxLength) {

            $this->logger->warning('The value is too long (magento). Trimming '.$value.' to '.$maxLength.' characters from '.strlen($value));

            return mb_substr($value ?? "", 0, $maxLength);
        }
        else {

            return $value ?? "";
        }
    }

    /**
     * Get the Shipping information of order.
     *
     * @param Address $shipping
     * @return $this
     */
    private function _getShippingInfo(Address $shipping): self
    {
        $state = '';
        if ($shipping->getRegion()) {
            $state = $this->getRegion($shipping->getRegion())->getCode();
        }

        $streetName1 = $this->trimChars($shipping->getStreetLine(1), 200);
        $streetName2 = $this->trimChars($shipping->getStreetLine(2), 200);
        $city = $this->trimChars($shipping->getCity(), 100);
        $phone = $this->trimChars($shipping->getTelephone(), 50);

        $this->_xmlData .= "\t<ShipTo>\n";
        $this->addXmlElement("Name", "<![CDATA[{$shipping->getFirstname()} {$shipping->getLastname()}]]>");
        $this->addXmlElement("Company", "<![CDATA[{$shipping->getCompany()}]]>");
        $this->addXmlElement("Address1", "<![CDATA[{$streetName1}]]>");
        $this->addXmlElement("Address2", "<![CDATA[{$streetName2}]]>");
        $this->addXmlElement("City", "<![CDATA[{$city}]]>");
        $this->addXmlElement("State", "<![CDATA[{$state}]]>");
        $this->addXmlElement("PostalCode", "<![CDATA[{$shipping->getPostcode()}]]>");
        $this->addXmlElement("Country", "<![CDATA[{$shipping->getCountryId()}]]>");
        $this->addXmlElement("Phone", "<![CDATA[{$phone}]]>");
        $this->_xmlData .= "\t</ShipTo>\n";

        return $this;
    }

    /**
     * Write the order item in xml response data
     *
     * @param Order $order
     * @return $this
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
            }// If Import only Child is selected, import only  child items
            elseif ($this->_importChild == ImportChild::CHILD_ONLY_VALUE && $orderItem->getProductType() == Type::TYPE_BUNDLE) {
                continue;
            }

            if (empty($price)) {
                $price = '0.00';
            }

            $this->_xmlData .= "\t<Item>\n";

            $this->addXmlElement("SKU", "<![CDATA[{$orderItem->getSku()}]]>");
            $this->addXmlElement("Name", "<![CDATA[{$name}]]>");
            $this->addXmlElement("ImageUrl", "<![CDATA[{$imageUrl}]]>");
            $this->addXmlElement("Weight", "<![CDATA[{$foreighWeight->getValue()}]]>");
            $this->addXmlElement("WeightUnits", "<![CDATA[{$foreighWeight->getUnit()}]]>");
            $this->addXmlElement("UnitPrice", "<![CDATA[{$price}]]>");
            $this->addXmlElement("Quantity", "<![CDATA[". (int)$orderItem->getQtyOrdered() ."]]>");

            /*
             * Check for the attributes
             */
            $this->_xmlData .= "\t<Options>\n";
            $attributeCodes = explode(',', $this->_attributes ?? '');
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
                // @todo Use AttributeRepository instead. // On exception log and continue.
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
        $this->addXmlElement("SKU", "<![CDATA[{$code}]]>");
        $this->addXmlElement("Name", '');
        $this->addXmlElement("Adjustment", 'true');
        $this->addXmlElement("Quantity", 1);
        $this->addXmlElement("UnitPrice", "<![CDATA[{$order->getDiscountAmount()}]]>");
        $this->_xmlData .= "\t</Item>\n";
    }
}
