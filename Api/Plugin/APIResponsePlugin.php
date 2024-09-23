<?php
namespace Auctane\Api\Plugin;

use Auctane\Api\Exception;
use Magento\Framework\Controller\Result\JsonFactory;

class APIResponsePlugin
{
    protected $jsonFactory;
    public function __construct(JsonFactory $jsonFactory)
    {
        $this->jsonFactory = $jsonFactory;
    }
    public function aroundExecute($subject, callable $proceed)
    {
        try {
            $response = $proceed();
            return $this->jsonFactory->create()->setData($response);
        } catch (Exception\ApiException $apiException) {
             return $this->jsonFactory->create()->setHttpResponseCode($apiException->getHttpStatusCode())->setData([
                 'status' => 'failure',
                 'message' => $apiException->getMessage()
             ]);
        } catch (\Exception $e) {
            return $this->jsonFactory->create()->setHttpResponseCode(500)->setData([
                'status' => 'failure',
                'message' => $e->getMessage()
            ]);
        }
    }
}
