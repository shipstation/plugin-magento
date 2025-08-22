<?php

namespace Magento\Framework\App\Config;

/**
 * Stub interface for Magento's ScopeConfigInterface
 */
interface ScopeConfigInterface
{
    public function getValue($path, $scopeType = null, $scopeCode = null);
    public function isSetFlag($path, $scopeType = null, $scopeCode = null);
}