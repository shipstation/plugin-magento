<?php

namespace Magento\Framework\App;

use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Stub class for Magento's ObjectManager
 */
class ObjectManager
{
    private static $instance;
    
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get($type)
    {
        switch ($type) {
            case JsonFactory::class:
                return new JsonFactory();
            case Http::class:
                return new Http();
            case ScopeConfigInterface::class:
                return new class implements ScopeConfigInterface {
                    public function getValue($path, $scopeType = null, $scopeCode = null) {
                        return null;
                    }
                    public function isSetFlag($path, $scopeType = null, $scopeCode = null) {
                        return false;
                    }
                };
            default:
                return new \stdClass();
        }
    }
}