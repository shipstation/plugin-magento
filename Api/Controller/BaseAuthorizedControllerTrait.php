<?php
namespace Auctane\Api\Controller;

use Auctane\Api\Api\AuthorizationInterface;
use Magento\Framework\App\ObjectManager;

trait BaseAuthorizedControllerTrait
{
    /** @var AuthorizationInterface */
    private AuthorizationInterface $authHandler;

    /**
     * Initialize the authorization handler.
     */
    protected function initAuthorization(): void
    {
        $objectManager = ObjectManager::getInstance();
        $this->authHandler = $objectManager->get(AuthorizationInterface::class);
    }
}
