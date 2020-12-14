<?php
namespace Auctane\Api\Model;

use Auctane\Api\Api\ConfigureShipstationInterface;
use Auctane\Api\Request\Authenticator;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Exception\AuthorizationException;

class ConfigureShipstation implements ConfigureShipstationInterface
{
    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

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
     * {@inheritdoc}
     */
    public function configureShipstation($option_key, $marketplace_key, $rates_url)
    {
        if(!$this->authenticator->authenticate())
        {
            throw new \Magento\Framework\Webapi\Exception(__('Authentication failed.'),
            0, \Magento\Framework\Webapi\Exception::HTTP_UNAUTHORIZED);
        }
        else
        {
            // Save under default scope
            $scopeType = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
            $this->configWriter->save('carriers/shipstation/option_key', $option_key, $scopeType);
            $this->configWriter->save('carriers/shipstation/marketplace_key', $marketplace_key);
            $this->configWriter->save('carriers/shipstation/rates_url', $rates_url, $scopeType);

            $this->flush();

            $response = [ 
                'option_key' => $option_key,
                'marketplace_key' => $marketplace_key,
                'rates_url'=> $rates_url,
            ];
        }

        return json_encode($response, JSON_UNESCAPED_LINE_TERMINATORS, JSON_UNESCAPED_SLASHES);
    }

    protected function flush()
    {
        $this->cacheTypeList->cleanType(\Magento\PageCache\Model\Cache\Type::TYPE_IDENTIFIER);
        $this->cacheTypeList->cleanType(\Magento\Framework\App\Cache\Type\Config::TYPE_IDENTIFIER);
    }
} 