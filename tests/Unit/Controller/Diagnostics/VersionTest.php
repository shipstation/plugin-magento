<?php

namespace Auctane\Api\Tests\Unit\Controller\Diagnostics;

use Auctane\Api\Controller\Diagnostics\Version;
use Auctane\Api\Tests\Utilities\TestCase;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Module\ModuleListInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test class for Version diagnostics controller
 * Tests version information endpoint functionality
 */
class VersionTest extends TestCase
{
    /**
     * @var Version
     */
    private $versionController;

    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var Json
     */
    private $jsonResponse;

    /**
     * @var ProductMetadataInterface|MockObject
     */
    private $productMetadata;

    /**
     * @var ModuleListInterface|MockObject
     */
    private $moduleList;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks
        $this->jsonFactory = new JsonFactory();
        $this->jsonResponse = new Json();
        $this->productMetadata = $this->createMock(ProductMetadataInterface::class);
        $this->moduleList = $this->createMock(ModuleListInterface::class);
        
        // Create controller instance with dependencies
        $this->versionController = new Version(
            $this->productMetadata,
            $this->moduleList
        );
        
        // Inject remaining dependencies using reflection
        $this->injectDependencies();
    }

    /**
     * Inject dependencies into the controller using reflection
     */
    private function injectDependencies(): void
    {
        $reflection = new \ReflectionClass($this->versionController);
        
        $jsonFactoryProperty = $reflection->getProperty('jsonFactory');
        $jsonFactoryProperty->setAccessible(true);
        $jsonFactoryProperty->setValue($this->versionController, $this->jsonFactory);
    }

    /**
     * Test that the version endpoint returns correct structure with Magento and module info
     */
    public function testExecuteReturnsVersionInfo(): void
    {
        // Arrange
        $expectedMagentoVersion = '2.4.6';
        $expectedMagentoEdition = 'Community';
        $expectedMagentoName = 'Magento';
        $expectedModuleInfo = [
            'name' => 'Auctane_Api',
            'setup_version' => '2.5.7'
        ];

        $this->productMetadata->method('getVersion')->willReturn($expectedMagentoVersion);
        $this->productMetadata->method('getEdition')->willReturn($expectedMagentoEdition);
        $this->productMetadata->method('getName')->willReturn($expectedMagentoName);
        $this->moduleList->method('getOne')->with('Auctane_Api')->willReturn($expectedModuleInfo);

        // Act
        $result = $this->versionController->execute();

        // Assert
        $this->assertInstanceOf(Json::class, $result);
        $responseData = $result->getData();
        
        $this->assertArrayHasKey('magento', $responseData);
        $this->assertArrayHasKey('module', $responseData);
        
        // Check Magento info
        $magentoInfo = $responseData['magento'];
        $this->assertEquals($expectedMagentoVersion, $magentoInfo['version']);
        $this->assertEquals($expectedMagentoEdition, $magentoInfo['edition']);
        $this->assertEquals($expectedMagentoName, $magentoInfo['name']);
        
        // Check module info
        $this->assertEquals($expectedModuleInfo, $responseData['module']);
    }    /**

     * Test executeAction returns the expected array structure
     */
    public function testExecuteActionReturnsCorrectStructure(): void
    {
        // Arrange
        $magentoVersion = '2.4.5';
        $magentoEdition = 'Enterprise';
        $magentoName = 'Magento';
        $moduleInfo = ['name' => 'Auctane_Api', 'setup_version' => '2.5.7'];

        $this->productMetadata->method('getVersion')->willReturn($magentoVersion);
        $this->productMetadata->method('getEdition')->willReturn($magentoEdition);
        $this->productMetadata->method('getName')->willReturn($magentoName);
        $this->moduleList->method('getOne')->with('Auctane_Api')->willReturn($moduleInfo);

        // Act
        $result = $this->versionController->executeAction();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('magento', $result);
        $this->assertArrayHasKey('module', $result);
        
        $this->assertIsArray($result['magento']);
        $this->assertArrayHasKey('version', $result['magento']);
        $this->assertArrayHasKey('edition', $result['magento']);
        $this->assertArrayHasKey('name', $result['magento']);
    }

    /**
     * Test that module list is called with correct module name
     */
    public function testModuleListCalledWithCorrectModuleName(): void
    {
        // Arrange
        $this->productMetadata->method('getVersion')->willReturn('2.4.6');
        $this->productMetadata->method('getEdition')->willReturn('Community');
        $this->productMetadata->method('getName')->willReturn('Magento');
        
        // Expect the module list to be called with the exact module name
        $this->moduleList->expects($this->once())
            ->method('getOne')
            ->with($this->equalTo('Auctane_Api'))
            ->willReturn(['name' => 'Auctane_Api']);

        // Act
        $this->versionController->executeAction();
    }

    /**
     * Test version endpoint with different Magento versions
     */
    public function testExecuteWithDifferentMagentoVersions(): void
    {
        $testCases = [
            ['2.4.6', 'Community', 'Magento'],
            ['2.4.5', 'Enterprise', 'Magento'],
            ['2.4.4', 'Commerce', 'Magento'],
        ];

        foreach ($testCases as [$version, $edition, $name]) {
            // Arrange
            $this->productMetadata = $this->createMock(ProductMetadataInterface::class);
            $this->productMetadata->method('getVersion')->willReturn($version);
            $this->productMetadata->method('getEdition')->willReturn($edition);
            $this->productMetadata->method('getName')->willReturn($name);
            
            $this->moduleList->method('getOne')->willReturn(['name' => 'Auctane_Api']);
            
            // Recreate controller with new metadata
            $this->versionController = new Version($this->productMetadata, $this->moduleList);
            $this->injectDependencies();

            // Act
            $result = $this->versionController->executeAction();

            // Assert
            $this->assertEquals($version, $result['magento']['version']);
            $this->assertEquals($edition, $result['magento']['edition']);
            $this->assertEquals($name, $result['magento']['name']);
        }
    }

    /**
     * Test that the controller doesn't require authorization
     */
    public function testControllerDoesNotRequireAuthorization(): void
    {
        // Arrange
        $this->productMetadata->method('getVersion')->willReturn('2.4.6');
        $this->productMetadata->method('getEdition')->willReturn('Community');
        $this->productMetadata->method('getName')->willReturn('Magento');
        $this->moduleList->method('getOne')->willReturn(['name' => 'Auctane_Api']);

        // Act
        $result = $this->versionController->execute();

        // Assert - Should succeed without authorization
        $this->assertInstanceOf(Json::class, $result);
        $this->assertEquals(200, $result->getHttpResponseCode());
    }
}