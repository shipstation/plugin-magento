<?php
namespace Auctane\Api\Controller;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

trait BaseControllerTrait
{
    /** @var JsonFactory  */
    protected JsonFactory $jsonFactory;
    /** @var Http  */
    protected Http $request;
    /** @var ScopeConfigInterface  */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * This method initializes things necessary for the BaseControllers functionality.
     *
     * @return void
     */
    public function initializeBaseControllerDependencies(): void
    {
        $objectManager = ObjectManager::getInstance();
        $this->jsonFactory = $objectManager->get(JsonFactory::class);
        $this->request = $objectManager->get(Http::class);
        $this->scopeConfig = $objectManager->get(ScopeConfigInterface::class);
    }
}
