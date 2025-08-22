<?php

namespace Auctane\Api\Tests\Unit\Exception;

use Auctane\Api\Exception\ApiException;
use Auctane\Api\Tests\Utilities\TestCase;

/**
 * Unit tests for ApiException base class
 * 
 * Tests the core functionality of the ApiException class including:
 * - Constructor behavior with message and HTTP status code
 * - HTTP status code assignment and retrieval
 * - Inheritance from base Exception class
 */
class ApiExceptionTest extends TestCase
{
    /**
     * Test ApiException constructor with default HTTP status code
     */
    public function testConstructorWithDefaultStatusCode(): void
    {
        $message = 'Test error message';
        $exception = new ApiException($message);

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(ApiException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(500, $exception->getHttpStatusCode());
        $this->assertEquals(0, $exception->getCode()); // Default Exception code
    }

    /**
     * Test ApiException constructor with custom HTTP status code
     */
    public function testConstructorWithCustomStatusCode(): void
    {
        $message = 'Custom error message';
        $httpStatusCode = 422;
        $exception = new ApiException($message, $httpStatusCode);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($httpStatusCode, $exception->getHttpStatusCode());
        $this->assertEquals(0, $exception->getCode()); // Default Exception code
    }

    /**
     * Test ApiException constructor with empty message
     */
    public function testConstructorWithEmptyMessage(): void
    {
        $exception = new ApiException('');

        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(500, $exception->getHttpStatusCode());
    }

    /**
     * Test getHttpStatusCode method returns correct value
     */
    public function testGetHttpStatusCode(): void
    {
        $testCases = [
            ['message' => 'Error 400', 'code' => 400],
            ['message' => 'Error 401', 'code' => 401],
            ['message' => 'Error 403', 'code' => 403],
            ['message' => 'Error 404', 'code' => 404],
            ['message' => 'Error 422', 'code' => 422],
            ['message' => 'Error 500', 'code' => 500],
            ['message' => 'Error 503', 'code' => 503],
        ];

        foreach ($testCases as $testCase) {
            $exception = new ApiException($testCase['message'], $testCase['code']);
            $this->assertEquals(
                $testCase['code'], 
                $exception->getHttpStatusCode(),
                "HTTP status code should match for: {$testCase['message']}"
            );
        }
    }

    /**
     * Test that ApiException properly inherits from base Exception
     */
    public function testInheritanceFromBaseException(): void
    {
        $message = 'Test inheritance';
        $httpStatusCode = 418; // I'm a teapot
        $exception = new ApiException($message, $httpStatusCode);

        // Test inheritance
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);

        // Test inherited methods work correctly
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode()); // Default Exception code
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    /**
     * Test ApiException can be thrown and caught
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        $message = 'Test throwable exception';
        $httpStatusCode = 400;

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage($message);

        throw new ApiException($message, $httpStatusCode);
    }

    /**
     * Test ApiException can be caught as base Exception
     */
    public function testExceptionCanBeCaughtAsBaseException(): void
    {
        $message = 'Test base exception catch';
        $httpStatusCode = 500;
        $caught = false;

        try {
            throw new ApiException($message, $httpStatusCode);
        } catch (\Exception $e) {
            $caught = true;
            $this->assertInstanceOf(ApiException::class, $e);
            $this->assertEquals($message, $e->getMessage());
            $this->assertEquals($httpStatusCode, $e->getHttpStatusCode());
        }

        $this->assertTrue($caught, 'Exception should have been caught');
    }

    /**
     * Test ApiException with various message types
     */
    public function testExceptionWithVariousMessageTypes(): void
    {
        $testMessages = [
            'Simple string message',
            'Message with special characters: !@#$%^&*()',
            'Message with numbers: 12345',
            'Message with unicode: café résumé',
            'Very long message: ' . str_repeat('Lorem ipsum dolor sit amet, ', 50),
        ];

        foreach ($testMessages as $message) {
            $exception = new ApiException($message, 400);
            $this->assertEquals($message, $exception->getMessage());
            $this->assertEquals(400, $exception->getHttpStatusCode());
        }
    }

    /**
     * Test ApiException with edge case HTTP status codes
     */
    public function testExceptionWithEdgeCaseStatusCodes(): void
    {
        $edgeCases = [
            0,      // Zero
            -1,     // Negative
            999,    // High number
            100,    // Informational
            200,    // Success (unusual for exception)
            300,    // Redirection
        ];

        foreach ($edgeCases as $statusCode) {
            $exception = new ApiException('Test message', $statusCode);
            $this->assertEquals($statusCode, $exception->getHttpStatusCode());
        }
    }

    /**
     * Test that HTTP status code is properly stored and retrieved
     */
    public function testHttpStatusCodeStorage(): void
    {
        $exception = new ApiException('Test', 404);
        
        // Test that the status code is stored correctly
        $this->assertEquals(404, $exception->getHttpStatusCode());
        
        // Test that calling getHttpStatusCode multiple times returns same value
        $this->assertEquals(404, $exception->getHttpStatusCode());
        $this->assertEquals(404, $exception->getHttpStatusCode());
    }

    /**
     * Test ApiException string representation
     */
    public function testExceptionStringRepresentation(): void
    {
        $message = 'Test string representation';
        $exception = new ApiException($message, 422);
        
        $stringRepresentation = (string) $exception;
        
        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString('ApiException', $stringRepresentation);
        $this->assertStringContainsString(__FILE__, $stringRepresentation);
    }
}