<?php

namespace Auctane\Api\Tests\Unit\Exception;

use Auctane\Api\Exception\ApiException;
use Auctane\Api\Exception\NotFoundException;
use Auctane\Api\Tests\Utilities\TestCase;

/**
 * Unit tests for NotFoundException class
 * 
 * Tests the NotFoundException functionality including:
 * - Constructor behavior with 404 status code
 * - Custom message and code handling
 * - Inheritance from ApiException
 */
class NotFoundExceptionTest extends TestCase
{
    /**
     * Test NotFoundException constructor with message only
     */
    public function testConstructorWithMessageOnly(): void
    {
        $message = 'Resource not found';
        $exception = new NotFoundException($message);

        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(NotFoundException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(404, $exception->getHttpStatusCode());
    }

    /**
     * Test NotFoundException constructor with custom code
     */
    public function testConstructorWithCustomCode(): void
    {
        $message = 'Page not found';
        $customCode = 410; // Gone
        $exception = new NotFoundException($message, $customCode);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($customCode, $exception->getHttpStatusCode());
    }

    /**
     * Test that NotFoundException returns correct HTTP status code by default
     */
    public function testHttpStatusCodeIs404ByDefault(): void
    {
        $exception = new NotFoundException('Test message');
        $this->assertEquals(404, $exception->getHttpStatusCode());
    }

    /**
     * Test NotFoundException with empty message
     */
    public function testConstructorWithEmptyMessage(): void
    {
        $exception = new NotFoundException('');
        
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(404, $exception->getHttpStatusCode());
    }

    /**
     * Test NotFoundException can be thrown and caught
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        $message = 'Test not found';
        
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($message);
        
        throw new NotFoundException($message);
    }

    /**
     * Test NotFoundException can be caught as ApiException
     */
    public function testExceptionCanBeCaughtAsApiException(): void
    {
        $message = 'Test API exception catch';
        $caught = false;

        try {
            throw new NotFoundException($message);
        } catch (ApiException $e) {
            $caught = true;
            $this->assertInstanceOf(NotFoundException::class, $e);
            $this->assertEquals($message, $e->getMessage());
            $this->assertEquals(404, $e->getHttpStatusCode());
        }

        $this->assertTrue($caught, 'Exception should have been caught as ApiException');
    }

    /**
     * Test various not found error scenarios
     */
    public function testVariousNotFoundScenarios(): void
    {
        $scenarios = [
            ['message' => 'Order not found', 'expectedCode' => 404],
            ['message' => 'Product not found', 'expectedCode' => 404],
            ['message' => 'Customer not found', 'expectedCode' => 404],
            ['message' => 'Shipment not found', 'expectedCode' => 404],
            ['message' => 'API endpoint not found', 'expectedCode' => 404],
        ];

        foreach ($scenarios as $scenario) {
            $exception = new NotFoundException($scenario['message']);
            $this->assertEquals($scenario['message'], $exception->getMessage());
            $this->assertEquals($scenario['expectedCode'], $exception->getHttpStatusCode());
        }
    }

    /**
     * Test NotFoundException with different HTTP codes
     */
    public function testExceptionWithDifferentHttpCodes(): void
    {
        $testCases = [
            ['message' => 'Not Found', 'code' => 404],
            ['message' => 'Gone', 'code' => 410],
            ['message' => 'Method Not Allowed', 'code' => 405],
            ['message' => 'Not Acceptable', 'code' => 406],
        ];

        foreach ($testCases as $testCase) {
            $exception = new NotFoundException($testCase['message'], $testCase['code']);
            $this->assertEquals($testCase['message'], $exception->getMessage());
            $this->assertEquals($testCase['code'], $exception->getHttpStatusCode());
        }
    }

    /**
     * Test inheritance chain
     */
    public function testInheritanceChain(): void
    {
        $exception = new NotFoundException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertInstanceOf(NotFoundException::class, $exception);
    }

    /**
     * Test NotFoundException with resource identifiers
     */
    public function testExceptionWithResourceIdentifiers(): void
    {
        $testCases = [
            'Order ID 12345 not found',
            'Product SKU ABC123 not found',
            'Customer email test@example.com not found',
            'Store ID 1 not found',
        ];

        foreach ($testCases as $message) {
            $exception = new NotFoundException($message);
            $this->assertEquals($message, $exception->getMessage());
            $this->assertEquals(404, $exception->getHttpStatusCode());
        }
    }

    /**
     * Test NotFoundException with URL paths
     */
    public function testExceptionWithUrlPaths(): void
    {
        $testCases = [
            'Path /api/orders/123 not found',
            'Endpoint /api/products/abc not found',
            'Route /admin/customers/456 not found',
        ];

        foreach ($testCases as $message) {
            $exception = new NotFoundException($message);
            $this->assertEquals($message, $exception->getMessage());
            $this->assertEquals(404, $exception->getHttpStatusCode());
        }
    }

    /**
     * Test NotFoundException string representation
     */
    public function testExceptionStringRepresentation(): void
    {
        $message = 'Test string representation';
        $exception = new NotFoundException($message);
        
        $stringRepresentation = (string) $exception;
        
        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString('NotFoundException', $stringRepresentation);
        $this->assertStringContainsString(__FILE__, $stringRepresentation);
    }
}