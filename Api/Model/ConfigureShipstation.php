<?php

namespace Auctane\Api\Model;

use Auctane\Api\Api\ConfigureShipstationInterface;
use Auctane\Api\Exception\AuthenticationFailedException;
use Auctane\Api\Request\Authenticator;
use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
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


    /**
     * ConfigureShipstation constructor.
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param Authenticator $authenticator
     */
    public function __construct(
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList,
        Authenticator $authenticator
    )
    {
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
        $this->authenticator = $authenticator;
    }

    /**
     * @param string $option_key
     * @param string $marketplace_key
     * @param string $rates_url
     * @return string
     * @throws AuthenticationFailedException
     */
    public function configureShipstation(string $option_key, string $marketplace_key, string $rates_url): string
    {
        $this->authenticator->authenticate();

        $this->configWriter->save('carriers/shipstation/option_key', $option_key);
        $this->configWriter->save('carriers/shipstation/marketplace_key', $marketplace_key);
        $this->configWriter->save('carriers/shipstation/rates_url', $rates_url);

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
