<?php

namespace Auctane\Api\Request;

use Magento\Backend\Model\Auth\Credential\StorageInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;


class Authenticator
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var StorageInterface
     */
    protected $storage;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(
        StorageInterface $storage,
        ScopeConfigInterface $scopeConfig,
        RequestInterface $request
     )
     {
        $this->storage = $storage;
        $this->scopeConfig = $scopeConfig;
        $this->request = $request;
     }

     public function authenticate()
     {
         // auth password tests where username is to avoid the pitfall of getting one value from params and one from headers.
         // They should both come from the same place.
        $authUser = $this->request->getParam('SS-UserName') ? $this->request->getParam('SS-UserName') : $this->request->getHeader('SS-UserName');
        $authPassword = $this->request->getParam('SS-UserName')? $this->request->getParam('SS-Password') : $this->request->getHeader('SS-Password');

        $apiKey = $this->scopeConfig->getValue(
            'shipstation_general/shipstation/ship_api_key'
        );

        $apiKeyFromShipStation = $this->request->getHeader('ShipStation-Access-Token');
        $apiKeyHasBeenGenerated = !empty($apiKey);
        $apiKeyHasBeenProvided = !empty($apiKeyFromShipStation);

        if ($apiKeyHasBeenGenerated
            && $apiKeyHasBeenProvided
            && ($apiKeyFromShipStation === $apiKey)) {
            $userAuthentication = true;
        } else {
            $userAuthentication = $this->storage->authenticate(
                $authUser,
                $authPassword
            );
        }

        return $userAuthentication;
     }
}