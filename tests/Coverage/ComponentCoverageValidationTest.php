<?php

declare(strict_types=1);

namespace Auctane\Api\Tests\Coverage;

use Auctane\Api\Tests\Utilities\TestCase;

/**
 * Component-specific coverage validation tests
 * 
 * Validates that specific components meet their individual coverage requirements
 * Requirements: 3.1, 3.2, 6.3
 */
class ComponentCoverageValidationTest extends TestCase
{
    private array $modelCoverageTargets = [
        'Auctane\Api\Model\Authorization' => 80.0,
        'Auctane\Api\Model\Action\Export' => 80.0,
        'Auctane\Api\Model\Action\ShipNotify' => 80.0,
        'Auctane\Api\Model\ApiKeyGenerator' => 80.0,
        'Auctane\Api\Model\Check' => 80.0,
        'Auctane\Api\Model\ConfigureShipstation' => 80.0,
        'Auctane\Api\Model\Weight' => 80.0,
        'Auctane\Api\Model\WeightAdapter' => 80.0,
    ];

    private array $controllerCoverageTargets = [
        'Auctane\Api\Controller\BaseController' => 85.0,
        'Auctane\Api\Controller\BaseAuthorizedController' => 85.0,
        'Auctane\Api\Controller\Diagnostics\Live' => 85.0,
        'Auctane\Api\Controller\Diagnostics\Version' => 85.0,
        'Auctane\Api\Controller\InventoryFetch\Index' => 85.0,
        'Auctane\Api\Controller\SalesOrdersExport\Index' => 85.0,
        'Auctane\Api\Controller\ShipmentNotification\Index' => 85.0,
    ];

    private array $exceptionCoverageTargets = [
        'Auctane\Api\Exception\ApiException' => 100.0,
        'Auctane\Api\Exception\AuthenticationFailedException' => 100.0,
        'Auctane\Api\Exception\AuthorizationException' => 100.0,
        'Auctane\Api\Exception\BadRequestException' => 100.0,
        'Auctane\Api\Exception\InvalidXmlException' => 100.0,
        'Auctane\Api\Exception\NotFoundException' => 100.0,
    ];

    /**
     * Test that each Model class individually meets coverage requirements
     * 
     * @test
     * @dataProvider modelClassProvider
     */
    public function testIndividualModelClassCoverage(string $className, float $expectedCoverage): void
    {
        $actualCoverage = $this->getClassCoverage($className);
        
        $this->assertGreaterThanOrEqual(
            $expectedCoverage,
            $actualCoverage,
            "Model class {$className} has {$actualCoverage}% coverage, should be at least {$expectedCoverage}%"
        );
    }

    /**
     * Test that each Controller class individually meets coverage requirements
     * 
     * @test
     * @dataProvider controllerClassProvider
     */
    public function testIndividualControllerClassCoverage(string $className, float $expectedCoverage): void
    {
        $actualCoverage = $this->getClassCoverage($className);
        
        $this->assertGreaterThanOrEqual(
            $expectedCoverage,
            $actualCoverage,
            "Controller class {$className} has {$actualCoverage}% coverage, should be at least {$expectedCoverage}%"
        );
    }

    /**
     * Test that each Exception class individually meets coverage requirements
     * 
     * @test
     * @dataProvider exceptionClassProvider
     */
    public function testIndividualExceptionClassCoverage(string $className, float $expectedCoverage): void
    {
        $actualCoverage = $this->getClassCoverage($className);
        
        $this->assertGreaterThanOrEqual(
            $expectedCoverage,
            $actualCoverage,
            "Exception class {$className} has {$actualCoverage}% coverage, should be at least {$expectedCoverage}%"
        );
    }

    /**
     * Test that no critical Model classes are missing from coverage validation
     * 
     * @test
     */
    public function testAllCriticalModelClassesAreValidated(): void
    {
        $existingModelFiles = $this->findPhpFiles('Api/Model');
        $validatedClasses = array_keys($this->modelCoverageTargets);
        
        $criticalClasses = [
            'Authorization.php',
            'Action/Export.php',
            'Action/ShipNotify.php',
            'ApiKeyGenerator.php',
            'Check.php',
            'ConfigureShipstation.php',
            'Weight.php',
            'WeightAdapter.php'
        ];
        
        foreach ($criticalClasses as $criticalClass) {
            $fullPath = 'Api/Model/' . $criticalClass;
            $className = $this->pathToClassName($fullPath);
            
            $this->assertContains(
                $className,
                $validatedClasses,
                "Critical model class {$className} should be included in coverage validation"
            );
        }
    }

    /**
     * Test that no critical Controller classes are missing from coverage validation
     * 
     * @test
     */
    public function testAllCriticalControllerClassesAreValidated(): void
    {
        $validatedClasses = array_keys($this->controllerCoverageTargets);
        
        $criticalClasses = [
            'BaseController.php',
            'BaseAuthorizedController.php',
            'Diagnostics/Live.php',
            'Diagnostics/Version.php',
            'InventoryFetch/Index.php',
            'SalesOrdersExport/Index.php',
            'ShipmentNotification/Index.php'
        ];
        
        foreach ($criticalClasses as $criticalClass) {
            $fullPath = 'Api/Controller/' . $criticalClass;
            $className = $this->pathToClassName($fullPath);
            
            $this->assertContains(
                $className,
                $validatedClasses,
                "Critical controller class {$className} should be included in coverage validation"
            );
        }
    }

    /**
     * Test that coverage validation script produces consistent results
     * 
     * @test
     */
    public function testCoverageValidationScriptConsistency(): void
    {
        // Use test coverage file if real one doesn't exist
        $coverageFile = file_exists('coverage/clover.xml') ? 'coverage/clover.xml' : 'coverage/test-clover.xml';
        
        if (!file_exists($coverageFile)) {
            $this->markTestSkipped('No coverage data available for testing');
        }

        // Run the validation script
        $output = [];
        $returnCode = 0;
        exec("php scripts/validate-coverage.php {$coverageFile} coverage/test-validation-report.json 2>&1", $output, $returnCode);
        
        // Check that the script ran successfully
        $this->assertFileExists('coverage/test-validation-report.json', 'Coverage validation should generate a report file');
        
        // Validate the report structure
        $report = json_decode(file_get_contents('coverage/test-validation-report.json'), true);
        $this->assertNotNull($report, 'Coverage report should be valid JSON');
        
        $this->assertArrayHasKey('overall_coverage', $report);
        $this->assertArrayHasKey('component_coverage', $report);
        $this->assertArrayHasKey('thresholds', $report);
        $this->assertArrayHasKey('status', $report);
        $this->assertArrayHasKey('validation_details', $report);
        $this->assertArrayHasKey('summary', $report);
        
        // Validate specific structure elements
        $this->assertIsNumeric($report['overall_coverage']);
        $this->assertIsArray($report['component_coverage']);
        $this->assertIsArray($report['thresholds']);
        $this->assertContains($report['status'], ['PASS', 'FAIL']);
        
        // Clean up
        if (file_exists('coverage/test-validation-report.json')) {
            unlink('coverage/test-validation-report.json');
        }
    }

    /**
     * Data provider for model classes
     * 
     * @return array
     */
    public function modelClassProvider(): array
    {
        $data = [];
        foreach ($this->modelCoverageTargets as $className => $coverage) {
            $data[] = [$className, $coverage];
        }
        return $data;
    }

    /**
     * Data provider for controller classes
     * 
     * @return array
     */
    public function controllerClassProvider(): array
    {
        $data = [];
        foreach ($this->controllerCoverageTargets as $className => $coverage) {
            $data[] = [$className, $coverage];
        }
        return $data;
    }

    /**
     * Data provider for exception classes
     * 
     * @return array
     */
    public function exceptionClassProvider(): array
    {
        $data = [];
        foreach ($this->exceptionCoverageTargets as $className => $coverage) {
            $data[] = [$className, $coverage];
        }
        return $data;
    }

    /**
     * Get coverage for a specific class
     * 
     * @param string $className
     * @return float
     */
    private function getClassCoverage(string $className): float
    {
        $coverageFile = 'coverage/coverage-report.json';
        
        if (file_exists($coverageFile)) {
            $reportData = json_decode(file_get_contents($coverageFile), true);
            
            if (isset($reportData['class_level_coverage'])) {
                foreach ($reportData['class_level_coverage'] as $component => $classes) {
                    if (isset($classes[$className])) {
                        return $classes[$className];
                    }
                }
            }
        }
        
        // Return a default value that will likely fail the test if no coverage data is found
        return 0.0;
    }

    /**
     * Convert file path to class name
     * 
     * @param string $path
     * @return string
     */
    private function pathToClassName(string $path): string
    {
        $className = str_replace(['/', '.php'], ['\\', ''], $path);
        
        if (!str_starts_with($className, 'Auctane\\Api\\')) {
            $className = 'Auctane\\Api\\' . $className;
        }
        
        return $className;
    }

    /**
     * Find all PHP files in a directory recursively
     * 
     * @param string $directory
     * @return array
     */
    private function findPhpFiles(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }
        
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
}