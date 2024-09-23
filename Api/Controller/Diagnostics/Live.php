<?php
namespace Auctane\Api\Controller\Diagnostics;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Live implements HttpGetActionInterface {
    public function execute() {
        return [
            'status' => 'alive'
        ];
    }
}
