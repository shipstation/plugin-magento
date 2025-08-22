<?php

// Fix malformed template patterns in test files
$patterns = [
    // Fix variable assignments like " . $var . " = value
    '/"\s*\.\s*\$(\w+)\s*\.\s*"\s*=/' => '$\1 =',
    
    // Fix string concatenations like "Bearer {" . $apiKey . "}"
    '/"Bearer \{\"\s*\.\s*\$(\w+)\s*\.\s*\"\}"/' => '"Bearer {$\1}"',
    
    // Fix array access like " . $var . "['key']
    '/"\s*\.\s*\$(\w+)\s*\.\s*"\[/' => '$\1[',
    
    // Fix catch statements like } catch (\Exception " . $e . ") {
    '/\} catch \(\\\\Exception\s+"\s*\.\s*\$(\w+)\s*\.\s*"\)\s*\{/' => '} catch (\Exception $\1) {',
    
    // Fix foreach statements like foreach (" . $var . " as
    '/foreach\s*\(\s*"\s*\.\s*\$(\w+)\s*\.\s*"\s+as/' => 'foreach ($\1 as',
    
    // Fix if statements like if (count(" . $var . ") >= 3)
    '/if\s*\(\s*count\(\s*"\s*\.\s*\$(\w+)\s*\.\s*"\s*\)\s*>=/' => 'if (count($\1) >=',
    
    // Fix method calls like " . $this . "->method
    '/"\s*\.\s*\$this\s*\.\s*"\s*->/' => '$this->',
    
    // Fix string interpolation in assertions like "Export took {" . $var . "}s"
    '/"([^"]*)\{\"\s*\.\s*\$(\w+)\s*\.\s*\"\}([^"]*)"/' => '"$1{$\2}$3"',
    
    // Fix simple string concatenation like "ORD-{" . $i . "}"
    '/"([^"]*)\{\"\s*\.\s*\$(\w+)\s*\.\s*\"\}([^"]*)"/' => '"$1{$\2}$3"',
    
    // Fix variable in string like " . $request . "->method
    '/"\s*\.\s*\$(\w+)\s*\.\s*"\s*->/' => '$\1->',
];

$testFiles = [
    'tests/Integration/ReliabilityTest.php',
    'tests/Integration/PerformanceTest.php', 
    'tests/Integration/MultiStoreConfigurationTest.php',
    'tests/Fixtures/Orders/OrderFixture.php'
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $originalContent = $content;
        
        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }
        
        if ($content !== $originalContent) {
            file_put_contents($file, $content);
            echo "Fixed patterns in $file\n";
        }
    }
}

echo "Template fixing complete\n";