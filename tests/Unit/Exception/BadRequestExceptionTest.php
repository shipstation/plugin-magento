<?php

namespace Auctane\Api\Tests\Unit\Exception;

use Auctane\Api\Exception\ApiException;
use Auctane\Api\Exception\BadRequestException;
use Auctane\Api\Tests\Utilities\TestCase;

/**
 * Unit tests for BadRequestException class
 * 
 * Tests the BadRequestException functionality including:
 * - Constructor behavior with 400 status code
 * - Custom message and code handling
 * - Inheritance from ApiException
 */
class BadRequestExceptionTest extends TestCase
{
    /**
     * Test BadRequestException constructor with message only
     */
    public function testConstructorWithMessageOnly(): void
    {
        $message = 'Invalid request parameters';
        $exception = new BadRequestException($message);

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(BadRequestException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(400, $exception->getHttpStatusCode());
    }

    /**
     * Test BadRequestException constructor with custom code
     */
    public function testConstructorWithCustomCode(): void
    {
        $message = 'Validation failed';
        $customCode = 422;
        $exception = new BadRequestException($message, $customCode);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($customCode, $exception->getHttpStatusCode());
    }

    /**
     * Test that BadRequestException returns correct HTTP status code by default
     */
    public function testHttpStatusCodeIs400ByDefault(): void
    {
        $exception = new BadRequestException('Test message');
        $this->assertEquals(400, $exception->getHttpStatusCode());
    }

    /**
     * Test BadRequestException with empty message
     */
    public function testConstructorWithEmptyMessage(): void
    {
        $exception = new BadRequestException('');
        
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(400, $exception->getHttpStatusCode());
    }

    /**
     * Test BadRequestException can be thrown and caught
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        $message = 'Test bad request';
        
        $this->expectException(BadRequestException::class);
        $this->expectExceptionMessage($message);
        
        throw new BadRequestException($message);
    }

    /**
     * Test BadRequestException can be caught as ApiException
     */
    public function testExceptionCanBeCaughtAsApiException(): void
    {
        $message = 'Test API exception catch';
        $caught = false;

        try {
            throw new BadRequestException($message);
        } catch (ApiException $e) {
            $caught = true;
            $this->assertInstanceOf(BadRequestException::class, $e);
            $this->assertEquals($message, $e->getMessage());
            $this->assertEquals(400, $e->getHttpStatusCode());
        }

        $this->assertTrue($caught, 'Exception should have been caught as ApiException');
    }

    /**
     * Test various bad request error scenarios
     */
    public function testVariousBadRequestScenarios(): void
    {
        $scenarios = [
            ['message' => 'Missing required parameter', 'expectedCode' => 400],
            ['message' => 'Invalid JSON format', 'expectedCode' => 400],
            ['message' => 'Parameter value out of range', 'expectedCode' => 400],
            ['message' => 'Malformed request body', 'expectedCode' => 400],
        ];

        foreach ($scenarios as $scenario) {
            $exception = new BadRequestException($scenario['message']);
            $this->assertEquals($scenario['message'], $exception->getMessage());
            $this->assertEquals($scenario['expectedCode'], $exception->getHttpStatusCode());
        }
    }

    /**
     * Test BadRequestException with different HTTP codes
     */
    public function testExceptionWithDifferentHttpCodes(): void
    {
        $testCases = [
            ['message' => 'Bad Request', 'code' => 400],
            ['message' => 'Unprocessable Entity', 'code' => 422],
            ['message' => 'Length Required', 'code' => 411],
            ['message' => 'Payload Too Large', 'code' => 413],
        ];

        foreach ($testCases as $testCase) {
            $exception = new BadRequestException($testCase['message'], $testCase['code']);
            $this->assertEquals($testCase['message'], $exception->getMessage());
            $this->assertEquals($testCase['code'], $exception->getHttpStatusCode());
        }
    }

    /**
     * Test inheritance chain
     */
    public function testInheritanceChain(): void
    {
        $exception = new BadRequestException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(BadRequestException::class, $exception);
    }

    /**
     * Test BadRequestException with long messages
     */
    public function testExceptionWithLongMessage(): void
    {
        $longMessage = str_repeat('This is a very long error message. ', 100);
        $exception = new BadRequestException($longMessage);
        
        $this->assertEquals($longMessage, $exception->getMessage());
        $this->assertEquals(400, $exception->getHttpStatusCode());
    }

    /**
     * Test BadRequestException with special characters in message
     */
    public function testExceptionWithSpecialCharacters(): void
    {
        $specialMessage = 'Error with special chars: !@#$%^&*()[]{}|;:,.<>?';
        $exception = new BadRequestException($specialMessage);
        
        $this->assertEquals($specialMessage, $exception->getMessage());
        $this->assertEquals(400, $exception->getHttpStatusCode());
    }
}