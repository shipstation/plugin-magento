<?php

namespace Auctane\Api\Tests\Unit\Exception;

use Auctane\Api\Exception\AuthenticationFailedException;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\Exception\LocalizedException;

/**
 * Unit tests for AuthenticationFailedException class
 * 
 * Tests the AuthenticationFailedException functionality including:
 * - Constructor behavior with default message
 * - Inheritance from LocalizedException
 * - Localized message handling
 */
class AuthenticationFailedExceptionTest extends TestCase
{
    /**
     * Test AuthenticationFailedException constructor with default message
     */
    public function testConstructorWithDefaultMessage(): void
    {
        $exception = new AuthenticationFailedException();

        $this->assertInstanceOf(LocalizedException::class, $exception);
        $this->assertInstanceOf(AuthenticationFailedException::class, $exception);
        $this->assertEquals('Authentication failed', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode()); // Default Exception code
    }

    /**
     * Test AuthenticationFailedException can be thrown and caught
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        $this->expectException(AuthenticationFailedException::class);
        $this->expectExceptionMessage('Authentication failed');
        
        throw new AuthenticationFailedException();
    }

    /**
     * Test AuthenticationFailedException can be caught as LocalizedException
     */
    public function testExceptionCanBeCaughtAsLocalizedException(): void
    {
        $caught = false;

        try {
            throw new AuthenticationFailedException();
        } catch (LocalizedException $e) {
            $caught = true;
            $this->assertInstanceOf(AuthenticationFailedException::class, $e);
            $this->assertEquals('Authentication failed', $e->getMessage());
        }

        $this->assertTrue($caught, 'Exception should have been caught as LocalizedException');
    }

    /**
     * Test AuthenticationFailedException can be caught as base Exception
     */
    public function testExceptionCanBeCaughtAsBaseException(): void
    {
        $caught = false;

        try {
            throw new AuthenticationFailedException();
        } catch (\Exception $e) {
            $caught = true;
            $this->assertInstanceOf(AuthenticationFailedException::class, $e);
            $this->assertEquals('Authentication failed', $e->getMessage());
        }

        $this->assertTrue($caught, 'Exception should have been caught as base Exception');
    }

    /**
     * Test inheritance chain
     */
    public function testInheritanceChain(): void
    {
        $exception = new AuthenticationFailedException();
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(LocalizedException::class, $exception);
        $this->assertInstanceOf(AuthenticationFailedException::class, $exception);
    }

    /**
     * Test that exception message is consistent
     */
    public function testConsistentMessage(): void
    {
        $exception1 = new AuthenticationFailedException();
        $exception2 = new AuthenticationFailedException();
        
        $this->assertEquals($exception1->getMessage(), $exception2->getMessage());
        $this->assertEquals('Authentication failed', $exception1->getMessage());
        $this->assertEquals('Authentication failed', $exception2->getMessage());
    }

    /**
     * Test exception in authentication scenarios
     */
    public function testAuthenticationScenarios(): void
    {
        // Test multiple instances behave consistently
        $scenarios = [
            'Invalid API key scenario',
            'Missing credentials scenario', 
            'Expired token scenario',
            'Malformed auth header scenario',
        ];

        foreach ($scenarios as $scenario) {
            $exception = new AuthenticationFailedException();
            $this->assertEquals('Authentication failed', $exception->getMessage());
            $this->assertInstanceOf(AuthenticationFailedException::class, $exception);
        }
    }

    /**
     * Test exception properties
     */
    public function testExceptionProperties(): void
    {
        $exception = new AuthenticationFailedException();
        
        // Test basic properties
        $this->assertEquals('Authentication failed', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertIsString($exception->getFile());
        $this->assertIsInt($exception->getLine());
        $this->assertIsArray($exception->getTrace());
        $this->assertIsString($exception->getTraceAsString());
    }

    /**
     * Test exception string representation
     */
    public function testExceptionStringRepresentation(): void
    {
        $exception = new AuthenticationFailedException();
        $stringRepresentation = (string) $exception;
        
        $this->assertStringContainsString('Authentication failed', $stringRepresentation);
        $this->assertStringContainsString('AuthenticationFailedException', $stringRepresentation);
        $this->assertStringContainsString(__FILE__, $stringRepresentation);
    }

    /**
     * Test multiple exception instances are independent
     */
    public function testMultipleInstancesAreIndependent(): void
    {
        $exception1 = new AuthenticationFailedException();
        $exception2 = new AuthenticationFailedException();
        
        // They should have the same message but be different objects
        $this->assertEquals($exception1->getMessage(), $exception2->getMessage());
        $this->assertNotSame($exception1, $exception2);
        
        // They should have different stack traces (different line numbers)
        $this->assertNotEquals($exception1->getLine(), $exception2->getLine());
    }

    /**
     * Test exception can be serialized and unserialized
     */
    public function testExceptionSerialization(): void
    {
        $originalException = new AuthenticationFailedException();
        
        $serialized = serialize($originalException);
        $unserializedException = unserialize($serialized);
        
        $this->assertInstanceOf(AuthenticationFailedException::class, $unserializedException);
        $this->assertEquals($originalException->getMessage(), $unserializedException->getMessage());
        $this->assertEquals($originalException->getCode(), $unserializedException->getCode());
    }
}