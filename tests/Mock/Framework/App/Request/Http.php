<?php

namespace Magento\Framework\App\Request;

use Magento\Framework\App\RequestInterface;

/**
 * Stub class for Magento's Http Request
 */
class Http implements RequestInterface
{
    private $params = [];
    private $headers = [];
    private $content = '';
    
    public function getParam($key, $default = null)
    {
        return $this->params[$key] ?? $default;
    }
    
    public function getParams()
    {
        return $this->params;
    }
    
    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }
    
    public function getHeaders()
    {
        return $this->headers;
    }
    
    public function getContent()
    {
        return $this->content;
    }
    
    public function isPost()
    {
        return true;
    }
    
    public function setParams(array $params)
    {
        $this->params = $params;
    }
    
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }
    
    public function setContent($content)
    {
        $this->content = $content;
    }
}