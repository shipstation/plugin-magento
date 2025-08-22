<?php

/**
 * PHPUnit bootstrap file for Auctane_Api module tests
 *
 * This file initializes the test environment and sets up autoloading
 * for running tests without a full Magento installation.
 */

declare(strict_types=1);

// Ensure we're using the Composer autoloader
$autoloader = require_once __DIR__ . '/../vendor/autoload.php';

// Set error reporting for tests
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// Set timezone to avoid warnings
date_default_timezone_set('UTC');

// Define constants that might be expected by Magento code
if (!defined('BP')) {
    define('BP', __DIR__ . '/..');
}

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Initialize test environment
echo "PHPUnit Bootstrap: Test environment initialized\n";
