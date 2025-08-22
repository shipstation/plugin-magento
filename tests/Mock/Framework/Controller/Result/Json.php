<?php

namespace Magento\Framework\Controller\Result;

/**
 * Stub class for Magento's Json result class
 */
class Json
{
    private $data;
    private $httpResponseCode = 200;

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setHttpResponseCode($code)
    {
        $this->httpResponseCode = $code;
        return $this;
    }

    public function getHttpResponseCode()
    {
        return $this->httpResponseCode;
    }
}