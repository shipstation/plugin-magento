<?php

namespace Auctane\Api\Test\Mock\Framework;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Mock implementation of Magento's JsonFactory
 * Provides JSON response creation functionality for testing
 */
class JsonFactory
{
    /**
     * @var TestCase
     */
    private TestCase $testCase;

    /**
     * Constructor
     *
     * @param TestCase $testCase
     */
    public function __construct(TestCase $testCase)
    {
        $this->testCase = $testCase;
    }

    /**
     * Create mock JsonFactory instance
     *
     * @return MockObject
     */
    public function createMock(): MockObject
    {
        $mock = $this->testCase->createMock('Magento\Framework\Controller\Result\JsonFactory');
        
        $mock->method('create')
            ->willReturnCallback([$this, 'create']);
            
        return $mock;
    }

    /**
     * Create JSON result object
     *
     * @return MockObject
     */
    public function create(): MockObject
    {
        return $this->createJsonResult();
    }

    /**
     * Create mock JSON result object
     *
     * @return MockObject
     */
    private function createJsonResult(): MockObject
    {
        $mock = $this->testCase->createMock('Magento\Framework\Controller\Result\Json');
        
        $responseData = null;
        $httpResponseCode = 200;
        $headers = [];
        
        $mock->method('setData')
            ->willReturnCallback(function ($data) use (&$responseData, $mock) {
                $responseData = $data;
                return $mock;
            });
            
        $mock->method('setHttpResponseCode')
            ->willReturnCallback(function ($code) use (&$httpResponseCode, $mock) {
                $httpResponseCode = $code;
                return $mock;
            });
            
        $mock->method('setHeader')
            ->willReturnCallback(function ($name, $value, $replace = false) use (&$headers, $mock) {
                $headers[$name] = $value;
                return $mock;
            });
            
        $mock->method('getData')
            ->willReturnCallback(function () use (&$responseData) {
                return $responseData;
            });
            
        $mock->method('getHttpResponseCode')
            ->willReturnCallback(function () use (&$httpResponseCode) {
                return $httpResponseCode;
            });
            
        $mock->method('getHeaders')
            ->willReturnCallback(function () use (&$headers) {
                return $headers;
            });
            
        // Add method to render JSON for testing
        $mock->method('renderResult')
            ->willReturnCallback(function () use (&$responseData, &$httpResponseCode) {
                http_response_code($httpResponseCode);
                return json_encode($responseData);
            });
            
        return $mock;
    }
}