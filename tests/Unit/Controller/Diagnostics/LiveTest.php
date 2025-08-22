<?php

namespace Auctane\Api\Tests\Unit\Controller\Diagnostics;

use Auctane\Api\Controller\Diagnostics\Live;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Test class for Live diagnostics controller
 * Tests health check endpoint functionality
 */
class LiveTest extends TestCase
{
    /**
     * @var Live
     */
    private $liveController;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Json
     */
    private $jsonResponse;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->jsonFactory = new JsonFactory();
        $this->jsonResponse = new Json();
        
        // Create controller instance
        $this->liveController = new Live();
        
        // Inject dependencies using reflection
        $this->injectDependencies();
    }

    /**
     * Inject dependencies into the controller using reflection
     */
    private function injectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->liveController);
        
        $jsonFactoryProperty = $reflection->getProperty('jsonFactory');
        $jsonFactoryProperty->setAccessible(true);
        $jsonFactoryProperty->setValue($this->liveController, $this->jsonFactory);
    }

    /**
     * Test that the live endpoint returns alive status
     */
    public function testExecuteReturnsAliveStatus(): void
    {
        // Act
        $result = $this->liveController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $result->getData();
        $this->assertArrayHasKey('status', $responseData);
        $this->assertEquals('alive', $responseData['status']);
    }

    /**
     * Test that executeAction returns the expected array structure
     */
    public function testExecuteActionReturnsCorrectStructure(): void
    {
        // Act
        $result = $this->liveController->executeAction();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('alive', $result['status']);
    }

    /**
     * Test that the controller doesn't require authorization (extends BaseController, not BaseAuthorizedController)
     */
    public function testControllerDoesNotRequireAuthorization(): void
    {
        // Arrange - No authorization setup needed
        
        // Act
        $result = $this->liveController->execute();

        // Assert - Should succeed without authorization
        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals(200, $result->getHttpResponseCode());
    }
}