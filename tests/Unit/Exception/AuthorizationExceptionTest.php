<?php

namespace Auctane\Api\Tests\Unit\Exception;

use Auctane\Api\Exception\ApiException;
use Auctane\Api\Exception\AuthorizationException;
use Auctane\Api\Tests\Utilities\TestCase;

/**
 * Unit tests for AuthorizationException class
 * 
 * Tests the AuthorizationException functionality including:
 * - Default constructor behavior with 401 status code
 * - Custom message handling
 * - Inheritance from ApiException
 */
class AuthorizationExceptionTest extends TestCase
{
    /**
     * Test AuthorizationException constructor with default values
     */
    public function testConstructorWithDefaults(): void
    {
        $exception = new AuthorizationException();

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(AuthorizationException::class, $exception);
        $this->assertEquals('Unauthorized to take this action', $exception->getMessage());
        $this->assertEquals(401, $exception->getHttpStatusCode());
    }

    /**
     * Test AuthorizationException constructor with custom message
     */
    public function testConstructorWithCustomMessage(): void
    {
        $customMessage = 'Access denied for this resource';
        $exception = new AuthorizationException($customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals(401, $exception->getHttpStatusCode());
    }

    /**
     * Test AuthorizationException constructor with custom message and code
     */
    public function testConstructorWithCustomMessageAndCode(): void
    {
        $customMessage = 'Forbidden access';
        $customCode = 403;
        $exception = new AuthorizationException($customMessage, $customCode);

        $this->assertEquals($customMessage, $exception->getMessage());
        $this->assertEquals($customCode, $exception->getHttpStatusCode());
    }

    /**
     * Test that AuthorizationException returns correct HTTP status code
     */
    public function testHttpStatusCodeIs401ByDefault(): void
    {
        $exception = new AuthorizationException();
        $this->assertEquals(401, $exception->getHttpStatusCode());
    }

    /**
     * Test AuthorizationException with empty message
     */
    public function testConstructorWithEmptyMessage(): void
    {
        $exception = new AuthorizationException('');
        
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(401, $exception->getHttpStatusCode());
    }

    /**
     * Test AuthorizationException can be thrown and caught
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        $message = 'Test authorization failure';
        
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage($message);
        
        throw new AuthorizationException($message);
    }

    /**
     * Test AuthorizationException can be caught as ApiException
     */
    public function testExceptionCanBeCaughtAsApiException(): void
    {
        $message = 'Test API exception catch';
        $caught = false;

        try {
            throw new AuthorizationException($message);
        } catch (ApiException $e) {
            $caught = true;
            $this->assertInstanceOf(AuthorizationException::class, $e);
            $this->assertEquals($message, $e->getMessage());
            $this->assertEquals(401, $e->getHttpStatusCode());
        }

        $this->assertTrue($caught, 'Exception should have been caught as ApiException');
    }

    /**
     * Test various authorization error scenarios
     */
    public function testVariousAuthorizationScenarios(): void
    {
        $scenarios = [
            ['message' => 'Invalid API key', 'expectedCode' => 401],
            ['message' => 'Token expired', 'expectedCode' => 401],
            ['message' => 'Insufficient permissions', 'expectedCode' => 401],
            ['message' => 'Account suspended', 'expectedCode' => 401],
        ];

        foreach ($scenarios as $scenario) {
            $exception = new AuthorizationException($scenario['message']);
            $this->assertEquals($scenario['message'], $exception->getMessage());
            $this->assertEquals($scenario['expectedCode'], $exception->getHttpStatusCode());
        }
    }

    /**
     * Test AuthorizationException with different HTTP codes
     */
    public function testExceptionWithDifferentHttpCodes(): void
    {
        $testCases = [
            ['message' => 'Unauthorized', 'code' => 401],
            ['message' => 'Forbidden', 'code' => 403],
            ['message' => 'Payment Required', 'code' => 402],
        ];

        foreach ($testCases as $testCase) {
            $exception = new AuthorizationException($testCase['message'], $testCase['code']);
            $this->assertEquals($testCase['message'], $exception->getMessage());
            $this->assertEquals($testCase['code'], $exception->getHttpStatusCode());
        }
    }

    /**
     * Test inheritance chain
     */
    public function testInheritanceChain(): void
    {
        $exception = new AuthorizationException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(AuthorizationException::class, $exception);
    }
}