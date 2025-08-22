<?php

namespace Auctane\Api\Tests\Unit\Exception;

use Auctane\Api\Exception\InvalidXmlException;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\Exception\LocalizedException;

/**
 * Unit tests for InvalidXmlException class
 * 
 * Tests the InvalidXmlException functionality including:
 * - Constructor behavior with LibXMLError array
 * - Error storage and retrieval
 * - Inheritance from LocalizedException
 * - Default error message handling
 */
class InvalidXmlExceptionTest extends TestCase
{
    /**
     * Create a mock LibXMLError object for testing
     */
    private function createMockLibXMLError(
        int $level = LIBXML_ERR_ERROR,
        int $code = 1,
        int $column = 1,
        string $message = 'Test XML error',
        string $file = '',
        int $line = 1
    ): \LibXMLError {
        $error = new \LibXMLError();
        $error->level = $level;
        $error->code = $code;
        $error->column = $column;
        $error->message = $message;
        $error->file = $file;
        $error->line = $line;
        
        return $error;
    }

    /**
     * Test InvalidXmlException constructor with single error
     */
    public function testConstructorWithSingleError(): void
    {
        $error = $this->createMockLibXMLError();
        $errors = [$error];
        
        $exception = new InvalidXmlException($errors);

        $this->assertInstanceOf(LocalizedException::class, $exception);
        $this->assertInstanceOf(InvalidXmlException::class, $exception);
        $this->assertEquals("Input Xml contains errors and couldn't be parsed.", $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertCount(1, $exception->getErrors());
    }

    /**
     * Test InvalidXmlException constructor with multiple errors
     */
    public function testConstructorWithMultipleErrors(): void
    {
        $errors = [
            $this->createMockLibXMLError(LIBXML_ERR_ERROR, 1, 1, 'First error'),
            $this->createMockLibXMLError(LIBXML_ERR_WARNING, 2, 5, 'Second error'),
            $this->createMockLibXMLError(LIBXML_ERR_FATAL, 3, 10, 'Third error'),
        ];
        
        $exception = new InvalidXmlException($errors);

        $this->assertEquals("Input Xml contains errors and couldn't be parsed.", $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertCount(3, $exception->getErrors());
    }

    /**
     * Test InvalidXmlException constructor with empty errors array
     */
    public function testConstructorWithEmptyErrors(): void
    {
        $errors = [];
        $exception = new InvalidXmlException($errors);

        $this->assertEquals("Input Xml contains errors and couldn't be parsed.", $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertCount(0, $exception->getErrors());
    }

    /**
     * Test getErrors method returns correct errors
     */
    public function testGetErrorsReturnsCorrectErrors(): void
    {
        $errors = [
            $this->createMockLibXMLError(LIBXML_ERR_ERROR, 1, 1, 'Parse error'),
            $this->createMockLibXMLError(LIBXML_ERR_WARNING, 2, 5, 'Warning message'),
        ];
        
        $exception = new InvalidXmlException($errors);
        $retrievedErrors = $exception->getErrors();

        $this->assertIsArray($retrievedErrors);
        $this->assertCount(2, $retrievedErrors);
        $this->assertEquals($errors[0]->message, $retrievedErrors[0]->message);
        $this->assertEquals($errors[1]->message, $retrievedErrors[1]->message);
    }

    /**
     * Test InvalidXmlException can be thrown and caught
     */
    public function testExceptionCanBeThrownAndCaught(): void
    {
        $errors = [$this->createMockLibXMLError()];
        
        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage("Input Xml contains errors and couldn't be parsed.");
        
        throw new InvalidXmlException($errors);
    }

    /**
     * Test InvalidXmlException can be caught as LocalizedException
     */
    public function testExceptionCanBeCaughtAsLocalizedException(): void
    {
        $errors = [$this->createMockLibXMLError()];
        $caught = false;

        try {
            throw new InvalidXmlException($errors);
        } catch (LocalizedException $e) {
            $caught = true;
            $this->assertInstanceOf(InvalidXmlException::class, $e);
            $this->assertEquals("Input Xml contains errors and couldn't be parsed.", $e->getMessage());
            $this->assertEquals($errors, $e->getErrors());
        }

        $this->assertTrue($caught, 'Exception should have been caught as LocalizedException');
    }

    /**
     * Test inheritance chain
     */
    public function testInheritanceChain(): void
    {
        $errors = [$this->createMockLibXMLError()];
        $exception = new InvalidXmlException($errors);
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\Throwable::class, $exception);
        $this->assertInstanceOf(LocalizedException::class, $exception);
        $this->assertInstanceOf(InvalidXmlException::class, $exception);
    }

    /**
     * Test various XML error scenarios
     */
    public function testVariousXmlErrorScenarios(): void
    {
        $scenarios = [
            [
                'errors' => [$this->createMockLibXMLError(LIBXML_ERR_ERROR, 1, 1, 'Unexpected end of file')],
                'description' => 'Unexpected end of file error'
            ],
            [
                'errors' => [$this->createMockLibXMLError(LIBXML_ERR_FATAL, 2, 5, 'Invalid character')],
                'description' => 'Invalid character error'
            ],
            [
                'errors' => [$this->createMockLibXMLError(LIBXML_ERR_WARNING, 3, 10, 'Deprecated element')],
                'description' => 'Deprecated element warning'
            ],
        ];

        foreach ($scenarios as $scenario) {
            $exception = new InvalidXmlException($scenario['errors']);
            $this->assertEquals("Input Xml contains errors and couldn't be parsed.", $exception->getMessage());
            $this->assertEquals($scenario['errors'], $exception->getErrors());
        }
    }

    /**
     * Test error levels are preserved
     */
    public function testErrorLevelsArePreserved(): void
    {
        $errors = [
            $this->createMockLibXMLError(LIBXML_ERR_WARNING, 1, 1, 'Warning'),
            $this->createMockLibXMLError(LIBXML_ERR_ERROR, 2, 2, 'Error'),
            $this->createMockLibXMLError(LIBXML_ERR_FATAL, 3, 3, 'Fatal'),
        ];
        
        $exception = new InvalidXmlException($errors);
        $retrievedErrors = $exception->getErrors();

        $this->assertEquals(LIBXML_ERR_WARNING, $retrievedErrors[0]->level);
        $this->assertEquals(LIBXML_ERR_ERROR, $retrievedErrors[1]->level);
        $this->assertEquals(LIBXML_ERR_FATAL, $retrievedErrors[2]->level);
    }

    /**
     * Test error details are preserved
     */
    public function testErrorDetailsArePreserved(): void
    {
        $error = $this->createMockLibXMLError(
            LIBXML_ERR_ERROR,
            123,
            45,
            'Detailed error message',
            'test.xml',
            67
        );
        
        $exception = new InvalidXmlException([$error]);
        $retrievedErrors = $exception->getErrors();
        $retrievedError = $retrievedErrors[0];

        $this->assertEquals(LIBXML_ERR_ERROR, $retrievedError->level);
        $this->assertEquals(123, $retrievedError->code);
        $this->assertEquals(45, $retrievedError->column);
        $this->assertEquals('Detailed error message', $retrievedError->message);
        $this->assertEquals('test.xml', $retrievedError->file);
        $this->assertEquals(67, $retrievedError->line);
    }

    /**
     * Test exception message is consistent regardless of errors
     */
    public function testConsistentExceptionMessage(): void
    {
        $scenarios = [
            [],
            [$this->createMockLibXMLError()],
            [
                $this->createMockLibXMLError(LIBXML_ERR_ERROR, 1, 1, 'Error 1'),
                $this->createMockLibXMLError(LIBXML_ERR_WARNING, 2, 2, 'Error 2'),
            ],
        ];

        foreach ($scenarios as $errors) {
            $exception = new InvalidXmlException($errors);
            $this->assertEquals("Input Xml contains errors and couldn't be parsed.", $exception->getMessage());
        }
    }

    /**
     * Test exception string representation
     */
    public function testExceptionStringRepresentation(): void
    {
        $errors = [$this->createMockLibXMLError()];
        $exception = new InvalidXmlException($errors);
        $stringRepresentation = (string) $exception;
        
        $this->assertStringContainsString("Input Xml contains errors and couldn't be parsed.", $stringRepresentation);
        $this->assertStringContainsString('InvalidXmlException', $stringRepresentation);
        $this->assertStringContainsString(__FILE__, $stringRepresentation);
    }

    /**
     * Test that errors array is not modified after construction
     */
    public function testErrorsArrayIsNotModified(): void
    {
        $originalErrors = [
            $this->createMockLibXMLError(LIBXML_ERR_ERROR, 1, 1, 'Original error'),
        ];
        
        $exception = new InvalidXmlException($originalErrors);
        
        // Modify the original array
        $originalErrors[] = $this->createMockLibXMLError(LIBXML_ERR_WARNING, 2, 2, 'Added error');
        
        // Exception should still have the original errors
        $this->assertCount(1, $exception->getErrors());
        $this->assertEquals('Original error', $exception->getErrors()[0]->message);
    }
}