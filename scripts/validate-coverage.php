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
    private array $thresholds = [
        'overall' => 80.0,
        'models' => 80.0,
        'controllers' => 85.0,
        'exceptions' => 100.0
    ];

    private array $classPatterns = [
        'models' => '/Api\/Model\/.*\.php$/',
        'controllers' => '/Api\/Controller\/.*\.php$/',
        'exceptions' => '/Api\/Exception\/.*\.php$/'
    ];

    public function validateCoverage(string $cloverFile): bool
    {
        if (!file_exists($cloverFile)) {
            echo "Coverage file not found: {$cloverFile}\n";
            return false;
        }

        $xml = simplexml_load_file($cloverFile);
        if (!$xml) {
            echo "Failed to parse coverage file: {$cloverFile}\n";
            return false;
        }

        $overallCoverage = $this->calculateOverallCoverage($xml);
        $componentCoverage = $this->calculateComponentCoverage($xml);

        $success = true;

        // Validate overall coverage
        if ($overallCoverage < $this->thresholds['overall']) {
            echo "FAIL: Overall coverage {$overallCoverage}% is below threshold {$this->thresholds['overall']}%\n";
            $success = false;
        } else {
            echo "PASS: Overall coverage {$overallCoverage}% meets threshold {$this->thresholds['overall']}%\n";
        }

        // Validate component coverage
        foreach ($componentCoverage as $component => $coverage) {
            $threshold = $this->thresholds[$component] ?? 0;
            if ($coverage < $threshold) {
                echo "FAIL: {$component} coverage {$coverage}% is below threshold {$threshold}%\n";
                $success = false;
            } else {
                echo "PASS: {$component} coverage {$coverage}% meets threshold {$threshold}%\n";
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
        $componentCoverage = [
            'models' => ['statements' => 0, 'covered' => 0],
            'controllers' => ['statements' => 0, 'covered' => 0],
            'exceptions' => ['statements' => 0, 'covered' => 0]
        ];

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

    public function generateCoverageReport(string $cloverFile, string $outputFile): void
    {
        if (!file_exists($cloverFile)) {
            echo "Coverage file not found: {$cloverFile}\n";
            return;
        }

        $xml = simplexml_load_file($cloverFile);
        if (!$xml) {
            echo "Failed to parse coverage file: {$cloverFile}\n";
            return;
        }

        $overallCoverage = $this->calculateOverallCoverage($xml);
        $componentCoverage = $this->calculateComponentCoverage($xml);

        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_coverage' => $overallCoverage,
            'component_coverage' => $componentCoverage,
            'thresholds' => $this->thresholds,
            'status' => $this->validateCoverage($cloverFile) ? 'PASS' : 'FAIL'
        ];

        file_put_contents($outputFile, json_encode($report, JSON_PRETTY_PRINT));
        echo "Coverage report generated: {$outputFile}\n";
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