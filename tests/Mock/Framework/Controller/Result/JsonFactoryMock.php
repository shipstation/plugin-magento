<?php

namespace Auctane\Api\Tests\Mock\Framework\Controller\Result;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Mock implementation of Magento's JsonFactory for testing
 */
class JsonFactoryMock
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
     * Create a mock JsonFactory
     *
     * @return MockObject
     */
    public function createMock(): MockObject
    {
        $mock = $this->testCase->createMock('Magento\Framework\Controller\Result\JsonFactory');
        
        // Mock the create method to return a Json result mock
        $jsonResult = $this->createJsonResultMock();
        $mock->method('create')->willReturn($jsonResult);
        
        return $mock;
    }

    /**
     * Create a mock Json result object
     *
     * @return MockObject
     */
    private function createJsonResultMock(): MockObject
    {
        $mock = $this->testCase->createMock('Magento\Framework\Controller\Result\Json');
        
        $responseData = null;
        $httpResponseCode = 200;
        
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
            
        $mock->method('getData')
            ->willReturnCallback(function () use (&$responseData) {
                return $responseData;
            });
            
        $mock->method('getHttpResponseCode')
            ->willReturnCallback(function () use (&$httpResponseCode) {
                return $httpResponseCode;
            });
        
        return $mock;
    }
}