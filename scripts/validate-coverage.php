<?php

/**
 * Coverage validation script
 * 
 * Validates that code coverage meets the required thresholds
 * Requirements: 3.1, 3.2, 6.3
 */

declare(strict_types=1);

class CoverageValidator
{
    private array $thresholds;
    private array $classPatterns;
    private string $configFile;

    public function __construct(string $configFile = 'coverage-config.json')
    {
        $this->configFile = $configFile;
        $this->loadConfiguration();
    }

    private function loadConfiguration(): void
    {
        if (file_exists($this->configFile)) {
            $config = json_decode(file_get_contents($this->configFile), true);
            $environment = $_ENV['COVERAGE_ENV'] ?? 'production';
            
            // Use environment-specific thresholds if available
            if (isset($config['environments'][$environment]['thresholds'])) {
                $this->thresholds = $config['environments'][$environment]['thresholds'];
            } else {
                $this->thresholds = $config['thresholds'];
            }
            
            $this->classPatterns = $config['component_patterns'];
        } else {
            // Fallback to default values
            $this->thresholds = [
                'overall' => 80.0,
                'models' => 80.0,
                'controllers' => 85.0,
                'exceptions' => 100.0
            ];

            $this->classPatterns = [
                'models' => '/Api\/Model\/.*\.php$/',
                'controllers' => '/Api\/Controller\/.*\.php$/',
                'exceptions' => '/Api\/Exception\/.*\.php$/'
            ];
        }
    }

    public function validateCoverage(string $cloverFile): bool
    {
        if (!file_exists($cloverFile)) {
            echo "❌ Coverage file not found: {$cloverFile}\n";
            return false;
        }

        $xml = simplexml_load_file($cloverFile);
        if (!$xml) {
            echo "❌ Failed to parse coverage file: {$cloverFile}\n";
            return false;
        }

        echo "📊 Code Coverage Validation Report\n";
        echo "==================================\n\n";

        $overallCoverage = $this->calculateOverallCoverage($xml);
        $componentCoverage = $this->calculateComponentCoverage($xml);
        $classLevelCoverage = $this->calculateClassLevelCoverage($xml);

        $success = true;

        // Validate overall coverage
        echo "Overall Coverage:\n";
        if ($overallCoverage < $this->thresholds['overall']) {
            echo "❌ FAIL: Overall coverage {$overallCoverage}% is below threshold {$this->thresholds['overall']}%\n";
            $success = false;
        } else {
            echo "✅ PASS: Overall coverage {$overallCoverage}% meets threshold {$this->thresholds['overall']}%\n";
        }
        echo "\n";

        // Validate component coverage
        echo "Component Coverage:\n";
        foreach ($componentCoverage as $component => $coverage) {
            $threshold = $this->thresholds[$component] ?? 0;
            if ($coverage < $threshold) {
                echo "❌ FAIL: {$component} coverage {$coverage}% is below threshold {$threshold}%\n";
                $success = false;
            } else {
                echo "✅ PASS: {$component} coverage {$coverage}% meets threshold {$threshold}%\n";
            }
        }
        echo "\n";

        // Show detailed class-level coverage for failed components
        if (!$success) {
            echo "Detailed Class Coverage (for failed components):\n";
            foreach ($classLevelCoverage as $component => $classes) {
                $threshold = $this->thresholds[$component] ?? 0;
                if ($componentCoverage[$component] < $threshold) {
                    echo "\n{$component} classes:\n";
                    foreach ($classes as $className => $coverage) {
                        $status = $coverage >= $threshold ? "✅" : "❌";
                        echo "  {$status} {$className}: {$coverage}%\n";
                    }
                }
            }
        }

        return $success;
    }

    private function calculateOverallCoverage(\SimpleXMLElement $xml): float
    {
        $metrics = $xml->project->metrics;
        $statements = (int)$metrics['statements'];
        $coveredStatements = (int)$metrics['coveredstatements'];

        if ($statements === 0) {
            return 0.0;
        }

        return round(($coveredStatements / $statements) * 100, 2);
    }

    private function calculateComponentCoverage(\SimpleXMLElement $xml): array
    {
        $componentCoverage = [];
        
        // Initialize component coverage arrays
        foreach ($this->classPatterns as $component => $pattern) {
            $componentCoverage[$component] = ['statements' => 0, 'covered' => 0];
        }

        foreach ($xml->project->package as $package) {
            foreach ($package->file as $file) {
                $filename = (string)$file['name'];
                
                foreach ($this->classPatterns as $component => $pattern) {
                    if (preg_match($pattern, $filename)) {
                        $metrics = $file->metrics;
                        $componentCoverage[$component]['statements'] += (int)$metrics['statements'];
                        $componentCoverage[$component]['covered'] += (int)$metrics['coveredstatements'];
                        break;
                    }
                }
            }
        }

        $results = [];
        foreach ($componentCoverage as $component => $data) {
            if ($data['statements'] === 0) {
                $results[$component] = 0.0;
            } else {
                $results[$component] = round(($data['covered'] / $data['statements']) * 100, 2);
            }
        }

        return $results;
    }

    private function calculateClassLevelCoverage(\SimpleXMLElement $xml): array
    {
        $classLevelCoverage = [];
        
        // Initialize component arrays
        foreach ($this->classPatterns as $component => $pattern) {
            $classLevelCoverage[$component] = [];
        }

        foreach ($xml->project->package as $package) {
            foreach ($package->file as $file) {
                $filename = (string)$file['name'];
                
                foreach ($this->classPatterns as $component => $pattern) {
                    if (preg_match($pattern, $filename)) {
                        $metrics = $file->metrics;
                        $statements = (int)$metrics['statements'];
                        $covered = (int)$metrics['coveredstatements'];
                        
                        $coverage = $statements === 0 ? 0.0 : round(($covered / $statements) * 100, 2);
                        
                        // Extract class name from filename
                        $className = $this->extractClassName($filename);
                        $classLevelCoverage[$component][$className] = $coverage;
                        break;
                    }
                }
            }
        }

        return $classLevelCoverage;
    }

    private function extractClassName(string $filename): string
    {
        // Convert file path to class name
        $className = str_replace(['/', '.php'], ['\\', ''], $filename);
        
        // Add namespace prefix if not present
        if (!str_starts_with($className, 'Auctane\\Api\\')) {
            $className = 'Auctane\\Api\\' . $className;
        }
        
        return $className;
    }

    public function generateCoverageReport(string $cloverFile, string $outputFile): void
    {
        if (!file_exists($cloverFile)) {
            echo "❌ Coverage file not found: {$cloverFile}\n";
            return;
        }

        $xml = simplexml_load_file($cloverFile);
        if (!$xml) {
            echo "❌ Failed to parse coverage file: {$cloverFile}\n";
            return;
        }

        $overallCoverage = $this->calculateOverallCoverage($xml);
        $componentCoverage = $this->calculateComponentCoverage($xml);
        $classLevelCoverage = $this->calculateClassLevelCoverage($xml);
        $isValid = $this->isValidCoverage($overallCoverage, $componentCoverage);

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => $_ENV['COVERAGE_ENV'] ?? 'production',
            'overall_coverage' => $overallCoverage,
            'component_coverage' => $componentCoverage,
            'class_level_coverage' => $classLevelCoverage,
            'thresholds' => $this->thresholds,
            'status' => $isValid ? 'PASS' : 'FAIL',
            'validation_details' => $this->getValidationDetails($overallCoverage, $componentCoverage),
            'summary' => $this->generateSummary($overallCoverage, $componentCoverage, $isValid)
        ];

        file_put_contents($outputFile, json_encode($report, JSON_PRETTY_PRINT));
        echo "📄 Coverage report generated: {$outputFile}\n";
    }

    private function isValidCoverage(float $overallCoverage, array $componentCoverage): bool
    {
        if ($overallCoverage < $this->thresholds['overall']) {
            return false;
        }

        foreach ($componentCoverage as $component => $coverage) {
            $threshold = $this->thresholds[$component] ?? 0;
            if ($coverage < $threshold) {
                return false;
            }
        }

        return true;
    }

    private function getValidationDetails(float $overallCoverage, array $componentCoverage): array
    {
        $details = [];

        // Overall validation
        $details['overall'] = [
            'coverage' => $overallCoverage,
            'threshold' => $this->thresholds['overall'],
            'status' => $overallCoverage >= $this->thresholds['overall'] ? 'PASS' : 'FAIL',
            'difference' => round($overallCoverage - $this->thresholds['overall'], 2)
        ];

        // Component validation
        foreach ($componentCoverage as $component => $coverage) {
            $threshold = $this->thresholds[$component] ?? 0;
            $details[$component] = [
                'coverage' => $coverage,
                'threshold' => $threshold,
                'status' => $coverage >= $threshold ? 'PASS' : 'FAIL',
                'difference' => round($coverage - $threshold, 2)
            ];
        }

        return $details;
    }

    private function generateSummary(float $overallCoverage, array $componentCoverage, bool $isValid): array
    {
        $passedComponents = 0;
        $totalComponents = count($componentCoverage) + 1; // +1 for overall

        if ($overallCoverage >= $this->thresholds['overall']) {
            $passedComponents++;
        }

        foreach ($componentCoverage as $component => $coverage) {
            $threshold = $this->thresholds[$component] ?? 0;
            if ($coverage >= $threshold) {
                $passedComponents++;
            }
        }

        return [
            'overall_status' => $isValid ? 'PASS' : 'FAIL',
            'passed_components' => $passedComponents,
            'total_components' => $totalComponents,
            'success_rate' => round(($passedComponents / $totalComponents) * 100, 2)
        ];
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    $cloverFile = $argv[1] ?? 'coverage/clover.xml';
    $outputFile = $argv[2] ?? 'coverage/coverage-report.json';

    $validator = new CoverageValidator();
    
    echo "Validating coverage from: {$cloverFile}\n";
    echo "----------------------------------------\n";
    
    $success = $validator->validateCoverage($cloverFile);
    $validator->generateCoverageReport($cloverFile, $outputFile);
    
    echo "----------------------------------------\n";
    echo $success ? "Coverage validation PASSED\n" : "Coverage validation FAILED\n";
    
    exit($success ? 0 : 1);
}