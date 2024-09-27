<?php
namespace Auctane\Api\Controller;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;

trait BaseControllerTrait
{
    /** @var JsonFactory  */
    protected JsonFactory $jsonFactory;
    /** @var Http  */
    protected Http $request;

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
    }
}
