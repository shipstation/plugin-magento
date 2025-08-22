<?php

namespace Auctane\Api\Tests\Unit\Controller;

use Auctane\Api\Controller\BaseController;
use Auctane\Api\Exception\ApiException;
use Auctane\Api\Exception\AuthorizationException;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for BaseController functionality
 * Tests authorization, error handling, CSRF validation bypass, and JSON response formatting
 */
class BaseControllerTest extends TestCase
{
    /**
     * @var BaseController|MockObject
     */
    private $baseController;

    /**
     * @var JsonFactory|MockObject
     */
    private $jsonFactory;

    /**
     * @var Json|MockObject
     */
    private $jsonResponse;

    /**
     * @var Http|MockObject
     */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonResponse = $this->mockFactory->createJsonResponseMock();
        $this->request = $this->mockFactory->createHttpRequestMock();
        
        // Configure JsonFactory to return our mock response
        $this->jsonFactory->method('create')->willReturn($this->jsonResponse);
        
        // Create a concrete implementation of BaseController for testing
        $this->baseController = new class extends BaseController {
            public $executeActionResult = ['status' => 'success', 'data' => 'test'];
            public $isAuthorized = true;
            public $shouldThrowException = false;
            public $exceptionToThrow = null;
            
            protected function executeAction(): mixed
            {
                if ($this->shouldThrowException && $this->exceptionToThrow) {
                    throw $this->exceptionToThrow;
                }
                return $this->executeActionResult;
            }
            
            protected function getIsAuthorized(): bool
            {
                return $this->isAuthorized;
            }
            
            // Expose protected method for testing
            public function testGetIsAuthorized(): bool
            {
                return $this->getIsAuthorized();
            }
        };
        
        // Inject dependencies using reflection to bypass constructor
        $this->injectDependencies();
    }

    /**
     * Inject dependencies into the controller using reflection
     */
    private function injectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->baseController);
        
        $jsonFactoryProperty = $reflection->getProperty('jsonFactory');
        $jsonFactoryProperty->setAccessible(true);
        $jsonFactoryProperty->setValue($this->baseController, $this->jsonFactory);
        
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->baseController, $this->request);
    }

    /**
     * Test successful execution with authorization
     */
    public function testExecuteWithAuthorization(): void
    {
        // Arrange
        $expectedData = ['status' => 'success', 'data' => 'test'];
        $this->baseController->executeActionResult = $expectedData;
        $this->baseController->isAuthorized = true;

        // Act
        $result = $this->baseController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals($expectedData, $this->jsonResponse->getData());
    }

    /**
     * Test execution fails when not authorized
     */
    public function testExecuteWithoutAuthorization(): void
    {
        // Arrange
        $this->baseController->isAuthorized = false;

        // Act
        $result = $this->baseController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $this->jsonResponse->getData();
        $this->assertEquals('failure', $responseData['status']);
        $this->assertStringContainsString('Authorization', $responseData['message']);
        $this->assertEquals(401, $this->jsonResponse->getHttpResponseCode());
    }

    /**
     * Test execution handles ApiException properly
     */
    public function testExecuteHandlesApiException(): void
    {
        // Arrange
        $exception = new class('Test API error', 400) extends ApiException {
            public function getHttpStatusCode(): int
            {
                return 400;
            }
        };
        
        $this->baseController->isAuthorized = true;
        $this->baseController->shouldThrowException = true;
        $this->baseController->exceptionToThrow = $exception;

        // Act
        $result = $this->baseController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $this->jsonResponse->getData();
        $this->assertEquals('failure', $responseData['status']);
        $this->assertEquals('Test API error', $responseData['message']);
        $this->assertEquals(400, $this->jsonResponse->getHttpResponseCode());
    }

    /**
     * Test execution handles generic Exception
     */
    public function testExecuteHandlesGenericException(): void
    {
        // Arrange
        $exception = new \Exception('Generic error');
        $this->baseController->isAuthorized = true;
        $this->baseController->shouldThrowException = true;
        $this->baseController->exceptionToThrow = $exception;

        // Act
        $result = $this->baseController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $this->jsonResponse->getData();
        $this->assertEquals('failure', $responseData['status']);
        $this->assertEquals('Generic error', $responseData['message']);
        $this->assertEquals(500, $this->jsonResponse->getHttpResponseCode());
    }

    /**
     * Test CSRF validation is bypassed (returns null)
     */
    public function testCreateCsrfValidationExceptionReturnsNull(): void
    {
        // Arrange
        $request = $this->createMock(RequestInterface::class);

        // Act
        $result = $this->baseController->createCsrfValidationException($request);

        // Assert
        $this->assertNull($result);
    }

    /**
     * Test CSRF validation is disabled (returns true)
     */
    public function testValidateForCsrfReturnsTrue(): void
    {
        // Arrange
        $request = $this->createMock(RequestInterface::class);

        // Act
        $result = $this->baseController->validateForCsrf($request);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test default authorization returns true
     */
    public function testDefaultAuthorizationReturnsTrue(): void
    {
        // Act
        $result = $this->baseController->testGetIsAuthorized();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test JSON response formatting with complex data
     */
    public function testJsonResponseFormattingWithComplexData(): void
    {
        // Arrange
        $complexData = [
            'status' => 'success',
            'data' => [
                'orders' => [
                    ['id' => 1, 'total' => 100.50],
                    ['id' => 2, 'total' => 75.25]
                ],
                'pagination' => [
                    'page' => 1,
                    'limit' => 10,
                    'total' => 2
                ]
            ],
            'metadata' => [
                'timestamp' => '2023-01-01T00:00:00Z',
                'version' => '1.0'
            ]
        ];
        
        $this->baseController->executeActionResult = $complexData;
        $this->baseController->isAuthorized = true;

        // Act
        $result = $this->baseController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals($complexData, $this->jsonResponse->getData());
    }

    /**
     * Test HTTP status codes for different scenarios
     */
    public function testHttpStatusCodes(): void
    {
        // Test successful response (default 200)
        $this->baseController->isAuthorized = true;
        $result = $this->baseController->execute();
        $this->assertEquals(200, $this->jsonResponse->getHttpResponseCode());

        // Reset response mock for next test
        $this->jsonResponse = $this->mockFactory->createJsonResponseMock();
        $this->jsonFactory->method('create')->willReturn($this->jsonResponse);

        // Test unauthorized (401)
        $this->baseController->isAuthorized = false;
        $result = $this->baseController->execute();
        $this->assertEquals(401, $this->jsonResponse->getHttpResponseCode());
    }

    /**
     * Test error response structure consistency
     */
    public function testErrorResponseStructure(): void
    {
        // Test with ApiException
        $this->baseController->isAuthorized = true;
        $this->baseController->shouldThrowException = true;
        $this->baseController->exceptionToThrow = new class('API Error', 422) extends ApiException {
            public function getHttpStatusCode(): int { return 422; }
        };

        $result = $this->baseController->execute();
        $responseData = $this->jsonResponse->getData();

        $this->assertArrayHasKey('status', $responseData);
        $this->assertArrayHasKey('message', $responseData);
        $this->assertEquals('failure', $responseData['status']);
        $this->assertEquals('API Error', $responseData['message']);
    }
}