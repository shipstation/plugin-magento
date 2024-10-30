<?php
namespace Auctane\Api\Api;

use Magento\Framework\App\Action\HttpDeleteActionInterface;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\HttpPutActionInterface;

interface HttpActionInterface extends HttpPostActionInterface, HttpGetActionInterface, HttpDeleteActionInterface, HttpPutActionInterface
{
}
