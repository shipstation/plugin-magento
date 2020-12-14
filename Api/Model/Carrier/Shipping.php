<?php

namespace Auctane\Api\Model\Carrier;

use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\CountryFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Directory\Helper\Data;
use Magento\Framework\Xml\Security;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Shipping\Helper\Carrier as CarrierHelper;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Simplexml\ElementFactory;
use Magento\Shipping\Model\Tracking\Result\StatusFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Zend\Http\Client;

/**
 * Custom shipping model
 */
class Shipping extends AbstractCarrierOnline implements CarrierInterface
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Zend\Http\Client
     */
    protected $zendClient;

    /**
     * @var \Magento\Framework\App\ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var \Magento\Framework\App\Config\Storage\WriterInterface
     */
    protected $configWriter;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var string
     */
    protected $_code = 'shipstation';

    /**
     * @var bool
     */
    protected $isFixed = true;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @var \ReflectionClass
     */
    private $class;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        Security $xmlSecurity,
        ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        StatusFactory $trackStatusFactory,
        RegionFactory $regionFactory,
        CountryFactory $countryFactory,
        CurrencyFactory $currencyFactory,
        Data $directoryData,
        StockRegistryInterface $stockRegistry,
        Client $zendClient,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        WriterInterface $configWriter,
        Cart $cart,
        array $data = []
    ) {

        parent::__construct(
            $scopeConfig,
            $rateErrorFactory,
            $logger,
            $xmlSecurity,
            $xmlElFactory,
            $rateFactory,
            $rateMethodFactory,
            $trackFactory,
            $trackErrorFactory,
            $trackStatusFactory,
            $regionFactory,
            $countryFactory,
            $currencyFactory,
            $directoryData,
            $stockRegistry,
            $data
        );

        $this->rateResultFactory = $rateFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->productMetadata = $productMetadata;
        $this->zendClient = $zendClient;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->class = new \ReflectionClass($this);
        $this->cart = $cart;
    }
    /**
     * Custom Shipping Rates Collector
     *
     * @param RateRequest $request
     * @return \Magento\Shipping\Model\Rate\Result|bool
     */
    public function collectRates(RateRequest $request)
    {
        try{
            if (!$this->getConfigFlag('active')) return false;

            // Set scope for data retreival
            $scopeTypeDefault = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

            // Get required data
            $optionKey = $this->_scopeConfig->getValue('carriers/shipstation/option_key', $scopeTypeDefault);
            $marketplaceKey = $this->_scopeConfig->getValue('carriers/shipstation/marketplace_key', $scopeTypeDefault);
            $ratesUrl = $this->_scopeConfig->getValue('carriers/shipstation/rates_url', $scopeTypeDefault);

            // Validate
            if (empty($optionKey)) return false;
            if (empty($marketplaceKey)) return false;
            if (empty($ratesUrl)) return false;

            // Creation of the data contract
            $shippingRequest = array();

            // If there's an already selected ShipStation shipping method then this method is being called 
            // expecting that this method will be returned as a verification. Use the verify endpoint. 
            // Otherwise, it's a standard rates request, use the rate endpoint.
            $currentMethod = $this->cart->getQuote()->getShippingAddress()->getShippingMethod();

            if($currentMethod && $this->_startsWith($currentMethod, $this->_code)) $isValidation = true;
            else $isValidation = false;

            if($isValidation)
            {
                // Strip out the carrier code to leave the quote UUID and supply this
                $currentMethod = str_replace($this->_code . '_', '', $currentMethod);
                $shippingRequest['quote_id'] = $currentMethod;
            }

            // Add cart and shipping data
            $shippingRequest = $this->_getCartDetails($shippingRequest, $request);

            // Add standard connection details
            $shippingRequest['connection_options'] = array
            (
                'option_key' => $optionKey,
                'marketplace_key' => $marketplaceKey
            );

            // Call ShipStation Endpoint
            $response = $this->_callApi ($ratesUrl, json_encode($shippingRequest));

            // If it didn't go so well, log the response info and don't pass any methods back
            if(!$response->isOk())
            {
                $this->_logger->error('[SHIPSTATION] From: ' . $this->class->getName());
                $this->_logger->error('[SHIPSTATION] Status Code: ' . $response->getStatusCode());
                $this->_logger->error('[SHIPSTATION] URL: ' . $ratesUrl);
                unset($shippingRequest['connection_options']); // Remove credentials from log
                $this->_logger->error('[SHIPSTATION] Request: ' . json_encode($shippingRequest));
                $this->_logger->error('[SHIPSTATION] Response: ' . $response->getBody());
                return false;
            }

            // Create object from response and add returned delivery services.
            $shippingResponse = json_decode($response->getBody());

            /** @var \Magento\Shipping\Model\Rate\Result $result */
            $result = $this->rateResultFactory->create();

            // Add each of the returned rates to the Result object
            foreach($shippingResponse->options as $deliveryOption)
                $result->append($this->_createShippingMethod($deliveryOption));

            return $result;
        }
        catch(\Exception $e)
        {
            $this->_logger->error('[SHIPSTATION] From: ' . $this->class->getName());
            $this->_logger->error('[SHIPSTATION] Message: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }

    public function processAdditionalValidation(\Magento\Framework\DataObject $request) {
        return true;
     }

    /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param \Magento\Framework\DataObject $request
     * @return \Magento\Framework\DataObject
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request) 
    {

    }

    /**
     * Trivial function to detect if a string starts with another string
     *
     * @param string $string
     * @param string $startString
     * @return bool
     */
    protected function _startsWith ($string, $startString) 
    { 
        $len = strlen($startString); 
        return (substr($string, 0, $len) === $startString); 
    } 

    /**
     * Posts supplied JSON to a supplied endpoint 
     *
     * @param string $endPoint
     * @param string $requestJson
     * @return \Zend\Http\Response
     */
    protected function _callApi ($endPoint, $requestJson) 
    { 
        $this->zendClient->reset();
        $this->zendClient->setUri($endPoint);
        $this->zendClient->setMethod(\Zend\Http\Request::METHOD_POST); 
        $this->zendClient->setHeaders(['Content-Type' => 'application/json','Accept' => 'application/json']);
        $this->zendClient->setMethod('POST');
        $this->zendClient->setRawBody($requestJson);
        $this->zendClient->setEncType('application/json');
        $this->zendClient->send();
        return $this->zendClient->getResponse();
    } 

    /**
     * Posts supplied JSON to a supplied endpoint 
     *
     * @param object $shipStationMethod
     * @return \Magento\Quote\Model\Quote\Address\RateResult\Method
     */
    protected function _createShippingMethod ($shipStationMethod) 
    { 
        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();
        // Must be the unique code specified
        $method->setCarrier($this->_code);
        $method->setCarrierTitle($this->getConfigData('title'));
        $method->setMethod($shipStationMethod->code);
        $method->setMethodTitle($shipStationMethod->display_name);
        $method->setMethodDescription($shipStationMethod->display_name);
        $shippingCost = (float)$shipStationMethod->cost->amount;
        $method->setPrice($shippingCost);
        $method->setCost($shippingCost);
        return $method;
    } 

    /**
     * Returns an object formatted for a ShipStation rates request
     *
     * @param array $shippingRequest
     * @param \RateRequest $request
     * @return array
     */
    protected function _getCartDetails($shippingRequest, $request)
    {
        // Set scope for retreiving data
        $scopeTypeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

        // Destination address lines are concatenated in the RateRequest object so explode here.
        $streetLines = explode("\n",$request->getDestStreet());
        $origStreetLines = explode("\n",$request->getDestStreet());

        $shippingRequest['origin_store'] = array
        (    
            'street_1' => $this->_scopeConfig->getValue('general/store_information/street_line1',  $scopeTypeStore),
            'street_2' => $this->_scopeConfig->getValue('general/store_information/street_line2',  $scopeTypeStore),
            'zip' => $this->_scopeConfig->getValue('general/store_information/postcode',  $scopeTypeStore),
            'city' => $this->_scopeConfig->getValue('general/store_information/city',  $scopeTypeStore),
            'state' => $this->_scopeConfig->getValue('general/store_information/region_id',  $scopeTypeStore),
            'country' => $this->_scopeConfig->getValue('general/store_information/country_id',  $scopeTypeStore),
        );
        $shippingRequest['origin'] = array
        (    
            'street_1' => $this->_scopeConfig->getValue('shipping/origin/street_line1',  $scopeTypeStore),
            'street_2' => $this->_scopeConfig->getValue('shipping/origin/street_line2',  $scopeTypeStore),
            'zip' => $this->_scopeConfig->getValue('shipping/origin/postcode',  $scopeTypeStore),
            'city' => $this->_scopeConfig->getValue('shipping/origin/city',  $scopeTypeStore),
            'state' => $this->_scopeConfig->getValue('shipping/origin/region_id',  $scopeTypeStore),
            'country' => $this->_scopeConfig->getValue('shipping/origin/country_id',  $scopeTypeStore),
        );

        $shippingRequest['destination'] = array
        (
            'street_1' => (count($streetLines) > 0 ? $streetLines[0] : null),
            'street_2' => (count($streetLines) > 1 ? $streetLines[1] : null),
            'zip' => $request->getDestPostcode(),
            'city' => $request->getDestCity(),
            'state' => $request->getDestRegionCode(),
            'country' => $request->getDestCountryId(),
        );
        $shippingRequest['items'] = array();
        if ($request->getAllItems()) {
            foreach ($request->getAllItems() as $item) {
                $shipstation_item = array
                (
                    'sku' => $item->getsku(),
                    'product_id' => $item->getitem_id(),
                    'name' => $item->getname(),
                    'weight' => array('units' => $this->_scopeConfig->getValue('general/locale/weight_unit', $scopeTypeStore), 
                                    'value' => $item->getWeight()),
                    'price_per_item' => array( 'currency' =>$this->storeManager->getStore()->getCurrentCurrency()->getCode(), 'amount' => strval($item->getPrice())),
                    'quantity' => $item->getQty(),
                );
                array_push($shippingRequest['items'], $shipstation_item);
            }
        }

        $shippingRequest['requested_currency_code'] = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $shippingRequest['magento_version'] = $this->productMetadata->getVersion();
        return $shippingRequest;
    }
} 