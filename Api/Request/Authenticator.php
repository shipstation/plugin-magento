<?php

namespace Auctane\Api\Request;

use Auctane\Api\Exception\AuthenticationFailedException;
use Magento\Backend\Model\Auth\Credential\StorageInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;


/**
 * Class Authenticator
 * @package Auctane\Api\Request
 */
class Authenticator
{
    /** @var StorageInterface */
    protected $storage;
    /** @var ScopeConfigInterface */
    private $scopeConfig;
    /** @var StoreManagerInterface */
    private $storeManager;


    /**
     * Authenticator constructor.
     * @param StorageInterface $storage
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StorageInterface $storage,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    )
    {
        $this->storage = $storage;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

    }

    /**
     * Authenticate a user and returns all store Ids linked to the api key.
     * Returns an empty array if authentication matches global store.
     *
     * @param Http $request
     * @return string[]
     * @throws AuthenticationFailedException
     */
    public function authenticate(Http $request): array
    {
        $storeIds = null;

        // Matching api key at store level.
        if ($apiKey = $request->getHeader('ShipStation-Access-Token')) {
            foreach ($this->storeManager->getStores() as $store) {
                $storeApiKey = $this->scopeConfig->getValue(
                    'shipstation_general/shipstation/ship_api_key',
                    ScopeInterface::SCOPE_STORE,
                    $store->getId()
                );

                if ($apiKey === $storeApiKey) {
                    $storeIds[] = $store->getId();
                }
            }
        }

        // Use Magento user instead.
        if (is_null($storeIds)) {
            // auth password tests where username is to avoid the pitfall of getting one value from params and one from headers.
            // They should both come from the same place.
            $authUser = $request->getParam('SS-UserName') ? $request->getParam('SS-UserName') : $request->getHeader('SS-UserName');
            $authPassword = $request->getParam('SS-UserName') ? $request->getParam('SS-Password') : $request->getHeader('SS-Password');

            if ($this->storage->authenticate($authUser, $authPassword)) {
                $storeIds = [];
            }
        }

        if (is_null($storeIds)) {
            throw new AuthenticationFailedException();
        }

        return $storeIds;
    }
}
