<?php
namespace Auctane\Api\Model;

use Auctane\Api\Api\AuthorizationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Authorization implements AuthorizationInterface
{
    /** @var ScopeConfigInterface  */
    protected ScopeConfigInterface $scopeConfig;
    /** @var StoreManagerInterface */
    protected StoreManagerInterface $storeManager;

    /**
     * This initializes the Authorization.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Determines whether a token is valid.
     *
     * @param string $token
     * @return bool
     */
    public function isAuthorized(string $token): bool
    {
        $validCredentials = false;
        foreach ($this->storeManager->getStores() as $store) {
            $storeApiKey = $this->scopeConfig->getValue(
                'shipstation_general/shipstation/ship_api_key',
                ScopeInterface::SCOPE_STORE,
                $store->getId()
            );
            $validCredentials = $token === $storeApiKey || $validCredentials;
        }
        return $validCredentials;
    }
}
