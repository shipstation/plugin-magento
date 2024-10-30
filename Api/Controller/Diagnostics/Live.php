<?php
namespace Auctane\Api\Controller\Diagnostics;

use Auctane\Api\Controller\BaseController;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Live extends BaseController implements HttpGetActionInterface
{
    /**
     * Endpoint used to determine if site is reachable.
     *
     * @return array
     */
    public function executeAction(): array
    {
        return [
            'status' => 'alive'
        ];
    }
}
