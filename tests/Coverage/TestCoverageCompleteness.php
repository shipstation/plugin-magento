<?php

declare(strict_types=1);

namespace Auctane\Api\Tests\Coverage;

use Auctane\Api\Tests\Utilities\TestCase;

/**
 * Test coverage completeness validation
 * 
 * Ensures that all important classes have corresponding test files
 * Requirements: 3.1, 3.2, 6.3
 */
class TestCoverageCompleteness extends TestCase
{
    /**
     * Test that all Model classes have corresponding test files
     * 
     * @test
     */
    public function testAllModelClassesHaveTests(): void
    {
        $modelClasses = $this->findPhpFiles('Api/Model');
        $testFiles = $this->findPhpFiles('tests/Unit/Model');
        
        $missingTests = [];
        
        foreach ($modelClasses as $modelClass) {
            $expectedTestFile = $this->getExpectedTestFile($modelClass, 'Api/Model', 'tests/Unit/Model');
            
            if (!in_array($expectedTestFile, $testFiles)) {
                $missingTests[] = $expectedTestFile;
            }
        }
        
        $this->assertEmpty($missingTests, 
            'Missing test files for Model classes: ' . implode(', ', $missingTests));
    }

    /**
     * Test that all Controller classes have corresponding test files
     * 
     * @test
     */
    public function testAllControllerClassesHaveTests(): void
    {
        $controllerClasses = $this->findPhpFiles('Api/Controller');
        $testFiles = $this->findPhpFiles('tests/Unit/Controller');
        
        $missingTests = [];
        
        foreach ($controllerClasses as $controllerClass) {
            // Skip base classes and traits
            if (strpos($controllerClass, 'Base') !== false || 
                strpos($controllerClass, 'Trait') !== false) {
                continue;
            }
            
            $expectedTestFile = $this->getExpectedTestFile($controllerClass, 'Api/Controller', 'tests/Unit/Controller');
            
            if (!in_array($expectedTestFile, $testFiles)) {
                $missingTests[] = $expectedTestFile;
            }
        }
        
        $this->assertEmpty($missingTests, 
            'Missing test files for Controller classes: ' . implode(', ', $missingTests));
    }

    /**
     * Test that all Exception classes have corresponding test files
     * 
     * @test
     */
    public function testAllExceptionClassesHaveTests(): void
    {
        $exceptionClasses = $this->findPhpFiles('Api/Exception');
        $testFiles = $this->findPhpFiles('tests/Unit/Exception');
        
        $missingTests = [];
        
        foreach ($exceptionClasses as $exceptionClass) {
            $expectedTestFile = $this->getExpectedTestFile($exceptionClass, 'Api/Exception', 'tests/Unit/Exception');
            
            if (!in_array($expectedTestFile, $testFiles)) {
                $missingTests[] = $expectedTestFile;
            }
        }
        
        $this->assertEmpty($missingTests, 
            'Missing test files for Exception classes: ' . implode(', ', $missingTests));
    }

    /**
     * Test that test files follow naming conventions
     * 
     * @test
     */
    public function testTestFilesFollowNamingConventions(): void
    {
        $testFiles = $this->findPhpFiles('tests/Unit');
        $invalidNames = [];
        
        foreach ($testFiles as $testFile) {
            $filename = basename($testFile, '.php');
            
            // Test files should end with 'Test'
            if (!str_ends_with($filename, 'Test')) {
                $invalidNames[] = $testFile;
            }
        }
        
        $this->assertEmpty($invalidNames, 
            'Test files should end with "Test": ' . implode(', ', $invalidNames));
    }

    /**
     * Test that all test classes extend the base TestCase
     * 
     * @test
     */
    public function testAllTestClassesExtendBaseTestCase(): void
    {
        $testFiles = $this->findPhpFiles('tests/Unit');
        $invalidClasses = [];
        
        foreach ($testFiles as $testFile) {
            $content = file_get_contents($testFile);
            
            // Skip if file doesn't contain a class
            if (!preg_match('/class\s+(\w+)/', $content, $matches)) {
                continue;
            }
            
            $className = $matches[1];
            
            // Check if class extends TestCase
            if (!preg_match('/class\s+' . preg_quote($className) . '\s+extends\s+TestCase/', $content)) {
                $invalidClasses[] = $testFile;
            }
        }
        
        $this->assertEmpty($invalidClasses, 
            'Test classes should extend TestCase: ' . implode(', ', $invalidClasses));
    }

    /**
     * Test that critical methods are tested
     * 
     * @test
     */
    public function testCriticalMethodsAreTested(): void
    {
        $criticalMethods = [
            'Api/Model/Authorization.php' => ['isAuthorized'],
            'Api/Controller/BaseController.php' => ['execute'],
            'Api/Controller/BaseAuthorizedController.php' => ['execute'],
            'Api/Model/Action/Export.php' => ['execute'],
            'Api/Model/Action/ShipNotify.php' => ['execute']
        ];
        
        $missingMethodTests = [];
        
        foreach ($criticalMethods as $classFile => $methods) {
            $testFile = $this->getExpectedTestFile($classFile, 'Api', 'tests/Unit');
            
            if (!file_exists($testFile)) {
                continue; // Already covered by other tests
            }
            
            $testContent = file_get_contents($testFile);
            
            foreach ($methods as $method) {
                // Look for test methods that test this method
                $testMethodPattern = '/public\s+function\s+test.*' . preg_quote($method, '/') . '/i';
                
                if (!preg_match($testMethodPattern, $testContent)) {
                    $missingMethodTests[] = "{$classFile}::{$method}";
                }
            }
        }
        
        $this->assertEmpty($missingMethodTests, 
            'Missing tests for critical methods: ' . implode(', ', $missingMethodTests));
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

    /**
     * Get expected test file path for a source file
     * 
     * @param string $sourceFile
     * @param string $sourceDir
     * @param string $testDir
     * @return string
     */
    private function getExpectedTestFile(string $sourceFile, string $sourceDir, string $testDir): string
    {
        $relativePath = str_replace($sourceDir, '', $sourceFile);
        $testPath = $testDir . $relativePath;
        
        // Replace .php with Test.php
        $testPath = str_replace('.php', 'Test.php', $testPath);
        
        return $testPath;
    }
}