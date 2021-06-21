<?php
namespace Auctane\Api\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class NoConfigNotification implements \Magento\Framework\Notification\MessageInterface
{
    protected $_scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    )
    {
        $this->_scopeConfig = $scopeConfig;
    }

   public function getIdentity()
   {
       // Retrieve unique message identity
       return 'shipstation_not_configured';
   }
   public function isDisplayed()
   {

        $scopeTypeDefault = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

        // If the extension is not enabled in the Shipping section then no need to display
        if (!$this->_scopeConfig->getValue('carriers/shipstation/active', $scopeTypeDefault)) return false;

        // If any of these are empty, it needs reconfiguring.
        $option_key = $this->_scopeConfig->getValue('carriers/shipstation/option_key', $scopeTypeDefault);
        error_log($option_key);
        if (empty($option_key)) return true;

        $marketplace_key = $this->_scopeConfig->getValue('carriers/shipstation/marketplace_key', $scopeTypeDefault);
        if (empty($marketplace_key)) return true;

        $rates_url = $this->_scopeConfig->getValue('carriers/shipstation/rates_url', $scopeTypeDefault);
        if (empty($rates_url)) return true;

        return false;
   }
   public function getText()
   {
       // message text
       return "The ShipStation Shipping Rates plugin has been installed but not configured. Please log in to ShipStation and configure Magento from there. This message can be removed by disabling ShipStation in the Shipping Methods configuration.";
   }
   public function getSeverity()
   {
       // Possible values: 
       // SEVERITY_CRITICAL
       // SEVERITY_MAJOR
       // SEVERITY_MINOR
       // SEVERITY_NOTICE
       return self::SEVERITY_CRITICAL;
   }
} 