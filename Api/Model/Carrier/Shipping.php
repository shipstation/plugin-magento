<?php

namespace Auctane\Api\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Helper\Carrier as CarrierHelper;
use Magento\Shipping\Model\Rate\Result;
use Magento\Framework\Xml\Security;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * Custom shipping model
 */
class Shipping extends AbstractCarrierOnline implements CarrierInterface
{
    protected $storeManager;
    protected $zendClient;
    protected $productMetadata;
    protected $configWriter;

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
    private $cart;

    private $class;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        Security $xmlSecurity,
        \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
        \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
        \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
        \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Directory\Helper\Data $directoryData,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Zend\Http\Client $zendClient,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ProductMetadataInterface $productMetadata,
        WriterInterface $configWriter,
        \Magento\Checkout\Model\Cart $cart,
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

        if (!$this->getConfigFlag('active')) return false;

        // Set scopes for getting environment variables
        $scopeTypeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $scopeTypeDefault = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        // Get required data
        $option_key = $this->_scopeConfig->getValue('carriers/shipstation/option_key', $scopeTypeDefault);
        $marketplace_key = $this->_scopeConfig->getValue('carriers/shipstation/marketplace_key', $scopeTypeDefault);
        $rates_url = $this->_scopeConfig->getValue('carriers/shipstation/rates_url', $scopeTypeDefault);
        $verify_url = $this->_scopeConfig->getValue('carriers/shipstation/verify_url', $scopeTypeDefault);

        // Validate
        if (empty($option_key)) return false;
        if (empty($marketplace_key)) return false;
        if (empty($rates_url)) return false;
        if (empty($verify_url)) return false;

        // Destination address lines are concatenated in the RateRequest object so explode here.
        $streetLines = explode("\n",$request->getDestStreet());
        $origStreetLines = explode("\n",$request->getDestStreet());

        // Creation of the data contract.
        $shippingRequest = array();
        $shippingRequest['origin_store'] = array
        (    
            'street_1' => $this->_scopeConfig->getValue('general/store_information/street_line1',  $scopeTypeStore),
            'street_2' => $this->_scopeConfig->getValue('general/store_information/street_line2',  $scopeTypeStore),
            'street_3' => null,
            'zip' => $this->_scopeConfig->getValue('general/store_information/postcode',  $scopeTypeStore),
            'city' => $this->_scopeConfig->getValue('general/store_information/city',  $scopeTypeStore),
            'state' => $this->_scopeConfig->getValue('general/store_information/region_id',  $scopeTypeStore),
            'country' => $this->_scopeConfig->getValue('general/store_information/country_id',  $scopeTypeStore),
        );
        $shippingRequest['origin'] = array
        (    
            'street_1' => $this->_scopeConfig->getValue('shipping/origin/street_line1',  $scopeTypeStore),
            'street_2' => $this->_scopeConfig->getValue('shipping/origin/street_line2',  $scopeTypeStore),
            'street_3' => null,
            'zip' => $this->_scopeConfig->getValue('shipping/origin/postcode',  $scopeTypeStore),
            'city' => $this->_scopeConfig->getValue('shipping/origin/city',  $scopeTypeStore),
            'state' => $this->_scopeConfig->getValue('shipping/origin/region_id',  $scopeTypeStore),
            'country' => $this->_scopeConfig->getValue('shipping/origin/country_id',  $scopeTypeStore),
        );

        $shippingRequest['destination'] = array
        (
            'street_1' => (count($streetLines) > 0 ? $streetLines[0] : null),
            'street_2' => (count($streetLines) > 1 ? $streetLines[1] : null),
            'street_3' => (count($streetLines) > 2 ? $streetLines[2] : null),
            'zip' => $request->getDestPostcode(),
            'city' => $request->getDestCity(),
            'state' => $request->getDestRegionCode(),
            'country' => $request->getDestCountryId(),
        );
        $shippingRequest['connection_options'] = array
        (
            'option_key' => $option_key,
            'marketplace_key' => $marketplace_key
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
                    'price_per_item' => array( 'currency' =>$this->storeManager->getStore()->getCurrentCurrency()->getCode(), 'value' => $item->getPrice()),
                    'quantity' => $item->getQty(),
                );
                array_push($shippingRequest['items'], $shipstation_item);
            }
        }

        $shippingRequest['requested_currency_code'] = $this->storeManager->getStore()->getCurrentCurrency()->getCode();
        $shippingRequest['magento_version'] = $this->productMetadata->getVersion();

        // Call ShipStation Endpoint
        $this->zendClient->reset();
        $this->zendClient->setUri($rates_url);
        $this->zendClient->setMethod(\Zend\Http\Request::METHOD_POST); 
       	$this->zendClient->setHeaders(['Content-Type' => 'application/json','Accept' => 'application/json']);
        $this->zendClient->setMethod('POST');
        $this->zendClient->setRawBody(json_encode($shippingRequest));
        $this->zendClient->setEncType('application/json');
        $this->zendClient->send();

        error_log('Test method: ' . $this->cart->getQuote()->getShippingAddress()->getShippingMethod()); 
        $response = $this->zendClient->getResponse();

        // If it didn't go so well, log the response info and don't pass any methods back
        if(!$response->isOk())
        {
            $this->_logger->error('[SHIPSTATION] From: ' . $this->class->getName());
            $this->_logger->error('[SHIPSTATION] Status Code: ' . $response->getStatusCode());
            $this->_logger->error('[SHIPSTATION] URL: ' . $rates_url);
            unset($shippingRequest['connection_options']);
            $this->_logger->error('[SHIPSTATION] Request: ' . json_encode($shippingRequest));
            $this->_logger->error('[SHIPSTATION] Response: ' . $response->getBody());
            return false;
        }

        // Create object from response and add returned delivery services.
        $deliveryOptions = json_decode($response->getBody());
        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();
        foreach($deliveryOptions->options as $deliveryOption)
        {
            /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
            $method = $this->rateMethodFactory->create();
            // Must be the unique code specified
            $method->setCarrier($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethod($deliveryOption->code);
            $method->setMethodTitle($deliveryOption->display_name);
            $method->setMethodDescription($deliveryOption->display_name);
            $shippingCost = (float)$deliveryOption->cost->amount;
            $method->setPrice($shippingCost);
            $method->setCost($shippingCost);
            $result->append($method);
        }

        return $result;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
     /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param \Magento\Framework\DataObject $request
     * @return \Magento\Framework\DataObject
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request) {}

    public function processAdditionalValidation(\Magento\Framework\DataObject $request) {
        return true;
     }
}