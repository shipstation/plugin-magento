<?php
namespace Auctane\Api\Plugin;

use Auctane\Api\Exception\AuthorizationException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class APIAuthorizationPlugin
{
    /** @var Http */
    private Http $request;
    /** @var ScopeConfigInterface */
    private ScopeConfigInterface $scopeConfig;
    /** @var StoreManagerInterface  */
    private StoreManagerInterface $storeManager;

    /**
     * Authenticator constructor.
     *
     * @param Http $request
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Http $request,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->request = $request;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Validates that the user can make api calls
     *
     * @throws AuthorizationException
     */
    public function aroundExecute($subject, callable $proceed)
    {
        $authorizationHeader = $this->request->getHeader('Authorization');
        $accessToken = explode(' ', $authorizationHeader)[1];

        $validCredentials = false;
        foreach ($this->storeManager->getStores() as $store) {
            $storeApiKey = $this->scopeConfig->getValue(
                'shipstation_general/shipstation/ship_api_key',
                ScopeInterface::SCOPE_STORE,
                $store->getId()
            );
            $validCredentials = $accessToken === $storeApiKey || $validCredentials;
        }

        if ($validCredentials === false) {
            throw new AuthorizationException();
        }
        return $proceed();
    }
}
