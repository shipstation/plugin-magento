<?php

namespace Auctane\Api\Model;

use Auctane\Api\Api\ConfigureShipstationInterface;
use Auctane\Api\Exception\AuthenticationFailedException;
use Auctane\Api\Request\Authenticator;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Webapi\Exception;
use Magento\PageCache\Model\Cache\Type;


/**
 * Class ConfigureShipstation
 * @package Auctane\Api\Model
 */
class ConfigureShipstation implements ConfigureShipstationInterface
{
    /**
     * @var WriterInterface
     */
    protected $configWriter;
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;
    /**
     * @var Authenticator
     */
    private $authenticator;
    /** @var Http */
    private $request;


    /**
     * ConfigureShipstation constructor.
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Authenticator $authenticator
     * @param Http $request
     */
    public function __construct(
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Authenticator $authenticator,
        Http $request
    )
    {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->authenticator = $authenticator;
        $this->request = $request;
    }

    /**
     * @param string|null $option_key
     * @param string|null $marketplace_key
     * @param string|null $rates_url
     * @return false|string
     * @throws AuthenticationFailedException
     * @throws Exception
     */
    public function configureShipstation(?string $option_key, ?string $marketplace_key, ?string $rates_url)
    {
        if (!$this->authenticator->authenticate($this->request)) {
            throw new Exception(__('Authentication failed.'), 0, Exception::HTTP_UNAUTHORIZED);
        }

        // Save under default scope
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $this->configWriter->save('carriers/shipstation/option_key', $option_key, $scopeType);
        $this->configWriter->save('carriers/shipstation/marketplace_key', $marketplace_key);
        $this->configWriter->save('carriers/shipstation/rates_url', $rates_url, $scopeType);

        $this->flush();

        $response = [
            'option_key' => $option_key,
            'marketplace_key' => $marketplace_key,
            'rates_url' => $rates_url,
        ];

        return json_encode($response, JSON_UNESCAPED_LINE_TERMINATORS, JSON_UNESCAPED_SLASHES);
    }

    /**
     * @return $this
     */
    protected function flush(): self
    {
        $this->cacheTypeList->cleanType(Type::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType(Config::TYPE_IDENTIFIER);
        return $this;
    }
}
