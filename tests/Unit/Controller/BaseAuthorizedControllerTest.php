<?php

namespace Auctane\Api\Tests\Unit\Controller;

use Auctane\Api\Api\AuthorizationInterface;
use Auctane\Api\Controller\BaseAuthorizedController;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for BaseAuthorizedController functionality
 * Tests authorization checking with mocked Authorization service
 */
class BaseAuthorizedControllerTest extends TestCase
{
    /**
     * @var BaseAuthorizedController|MockObject
     */
    private $baseAuthorizedController;

    /**
     * @var AuthorizationInterface|MockObject
     */
    private $authHandler;

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
        $this->authHandler = $this->createMock(AuthorizationInterface::class);
        $this->jsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonResponse = $this->mockFactory->createJsonResponseMock();
        $this->request = $this->mockFactory->createHttpRequestMock();
        
        // Configure JsonFactory to return our mock response
        $this->jsonFactory->method('create')->willReturn($this->jsonResponse);
        
        // Create a concrete implementation of BaseAuthorizedController for testing
        $this->baseAuthorizedController = new class extends BaseAuthorizedController {
            public $executeActionResult = ['status' => 'success', 'data' => 'authorized'];
            public $shouldThrowException = false;
            public $exceptionToThrow = null;
            
            protected function executeAction(): mixed
            {
                if ($this->shouldThrowException && $this->exceptionToThrow) {
                    throw $this->exceptionToThrow;
                }
                return $this->executeActionResult;
            }
        };
        
        // Inject dependencies using reflection
        $this->injectDependencies();
    }    /**

     * Inject dependencies into the controller using reflection
     */
    private function injectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->baseAuthorizedController);
        
        // Inject JsonFactory
        $jsonFactoryProperty = $reflection->getProperty('jsonFactory');
        $jsonFactoryProperty->setAccessible(true);
        $jsonFactoryProperty->setValue($this->baseAuthorizedController, $this->jsonFactory);
        
        // Inject Request
        $requestProperty = $reflection->getProperty('request');
        $requestProperty->setAccessible(true);
        $requestProperty->setValue($this->baseAuthorizedController, $this->request);
        
        // Inject AuthHandler
        $authHandlerProperty = $reflection->getProperty('authHandler');
        $authHandlerProperty->setAccessible(true);
        $authHandlerProperty->setValue($this->baseAuthorizedController, $this->authHandler);
    }

    /**
     * Test successful authorization with valid Bearer token
     */
    public function testGetIsAuthorizedWithValidToken(): void
    {
        // Arrange
        $validToken = 'valid-api-token-123';
        $authorizationHeader = 'Bearer ' . $validToken;
        
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn($authorizationHeader);
            
        $this->authHandler->method('isAuthorized')
            ->with($validToken)
            ->willReturn(true);

        // Act
        $result = $this->baseAuthorizedController->getIsAuthorized();

        // Assert
        $this->assertTrue($result);
    }

    /**
     * Test authorization fails with invalid token
     */
    public function testGetIsAuthorizedWithInvalidToken(): void
    {
        // Arrange
        $invalidToken = 'invalid-token';
        $authorizationHeader = 'Bearer ' . $invalidToken;
        
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn($authorizationHeader);
            
        $this->authHandler->method('isAuthorized')
            ->with($invalidToken)
            ->willReturn(false);

        // Act
        $result = $this->baseAuthorizedController->getIsAuthorized();

        // Assert
        $this->assertFalse($result);
    }

    /**
     * Test authorization with malformed header (no Bearer prefix)
     */
    public function testGetIsAuthorizedWithMalformedHeader(): void
    {
        // Arrange
        $malformedHeader = 'invalid-header-format';
        
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn($malformedHeader);

        // Expect this to cause an error when trying to split the header
        $this->expectException(\Exception::class);

        // Act
        $this->baseAuthorizedController->getIsAuthorized();
    }  
  /**
     * Test authorization with empty header
     */
    public function testGetIsAuthorizedWithEmptyHeader(): void
    {
        // Arrange
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn(null);

        // Expect this to cause an error when trying to split null
        $this->expectException(\Exception::class);

        // Act
        $this->baseAuthorizedController->getIsAuthorized();
    }

    /**
     * Test successful execution with valid authorization
     */
    public function testExecuteWithValidAuthorization(): void
    {
        // Arrange
        $validToken = 'valid-token';
        $authorizationHeader = 'Bearer ' . $validToken;
        $expectedData = ['status' => 'success', 'data' => 'authorized'];
        
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn($authorizationHeader);
            
        $this->authHandler->method('isAuthorized')
            ->with($validToken)
            ->willReturn(true);
            
        $this->baseAuthorizedController->executeActionResult = $expectedData;

        // Act
        $result = $this->baseAuthorizedController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals($expectedData, $this->jsonResponse->getData());
        $this->assertEquals(200, $this->jsonResponse->getHttpResponseCode());
    }

    /**
     * Test execution fails with invalid authorization
     */
    public function testExecuteWithInvalidAuthorization(): void
    {
        // Arrange
        $invalidToken = 'invalid-token';
        $authorizationHeader = 'Bearer ' . $invalidToken;
        
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn($authorizationHeader);
            
        $this->authHandler->method('isAuthorized')
            ->with($invalidToken)
            ->willReturn(false);

        // Act
        $result = $this->baseAuthorizedController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $this->jsonResponse->getData();
        $this->assertEquals('failure', $responseData['status']);
        $this->assertStringContainsString('Authorization', $responseData['message']);
        $this->assertEquals(401, $this->jsonResponse->getHttpResponseCode());
    }

    /**
     * Test execution with missing authorization header
     */
    public function testExecuteWithMissingAuthorizationHeader(): void
    {
        // Arrange
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn(null);

        // Act
        $result = $this->baseAuthorizedController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $this->jsonResponse->getData();
        $this->assertEquals('failure', $responseData['status']);
        $this->assertEquals(500, $this->jsonResponse->getHttpResponseCode());
    }    /*
*
     * Test authorization with different token formats
     */
    public function testGetIsAuthorizedWithDifferentTokenFormats(): void
    {
        $testCases = [
            ['Bearer token123', 'token123', true],
            ['Bearer another-valid-token', 'another-valid-token', true],
            ['Bearer invalid-token', 'invalid-token', false],
        ];

        foreach ($testCases as [$header, $expectedToken, $authResult]) {
            // Arrange
            $this->request = $this->mockFactory->createHttpRequestMock();
            $this->request->method('getHeader')
                ->with('Authorization')
                ->willReturn($header);
                
            $this->authHandler->method('isAuthorized')
                ->with($expectedToken)
                ->willReturn($authResult);
                
            // Re-inject dependencies for each test case
            $this->injectDependencies();

            // Act
            $result = $this->baseAuthorizedController->getIsAuthorized();

            // Assert
            $this->assertEquals($authResult, $result, "Failed for header: $header");
        }
    }

    /**
     * Test that authorization handler is called with correct token
     */
    public function testAuthorizationHandlerCalledWithCorrectToken(): void
    {
        // Arrange
        $expectedToken = 'test-token-456';
        $authorizationHeader = 'Bearer ' . $expectedToken;
        
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn($authorizationHeader);
            
        // Expect the authorization handler to be called with the exact token
        $this->authHandler->expects($this->once())
            ->method('isAuthorized')
            ->with($this->equalTo($expectedToken))
            ->willReturn(true);

        // Act
        $this->baseAuthorizedController->getIsAuthorized();
    }

    /**
     * Test authorization with whitespace in token
     */
    public function testGetIsAuthorizedWithWhitespaceInToken(): void
    {
        // Arrange
        $tokenWithSpaces = 'token with spaces';
        $authorizationHeader = 'Bearer ' . $tokenWithSpaces;
        
        $this->request->method('getHeader')
            ->with('Authorization')
            ->willReturn($authorizationHeader);
            
        $this->authHandler->method('isAuthorized')
            ->with($tokenWithSpaces)
            ->willReturn(true);

        // Act
        $result = $this->baseAuthorizedController->getIsAuthorized();

        // Assert
        $this->assertTrue($result);
    }
}