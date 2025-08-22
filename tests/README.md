# Auctane_Api Testing Framework

This directory contains the standalone unit testing framework for the Auctane_Api Magento 2 module.

## Overview

The testing framework is designed to run independently without requiring a full Magento installation. It uses PHPUnit for testing, PHPStan for static analysis, and PHP CodeSniffer for coding standards validation.

## Directory Structure

```
tests/
├── Unit/                           # Unit tests mirror the Api/ structure
│   ├── Controller/                 # Controller tests with mocked dependencies
│   ├── Model/                      # Business logic and data model tests
│   ├── Exception/                  # Exception handling tests
│   └── Helper/                     # Utility class tests
├── Mock/                           # Mock implementations for Magento dependencies
│   ├── Framework/                  # Core Magento framework mocks
│   ├── Store/                      # Store management mocks
│   └── Config/                     # Configuration system mocks
├── Fixtures/                       # Test data and sample payloads
│   ├── Requests/                   # Sample API request data
│   ├── Responses/                  # Expected API response data
│   └── Orders/                     # Sample order data
├── Utilities/                      # Test helper classes and factories
└── bootstrap.php                   # Test environment initialization
```

## Available Commands

### Testing
- `composer test` - Run all unit tests
- `composer test-coverage` - Run tests with HTML coverage report
- `composer test-coverage-xml` - Run tests with XML coverage report

### Code Quality
- `composer phpstan` - Run PHPStan static analysis
- `composer phpcs` - Run PHP CodeSniffer for coding standards
- `composer phpcbf` - Fix coding standards automatically
- `composer quality` - Run both PHPStan and CodeSniffer
- `composer analyze` - Run tests and quality checks

## Requirements

- PHP 8.0 or higher
- Composer for dependency management
- Xdebug or PCOV extension for code coverage (optional)

## Getting Started

1. Install dependencies:
   ```bash
   composer install
   ```

2. Run tests:
   ```bash
   composer test
   ```

3. Check code quality:
   ```bash
   composer quality
   ```

## Configuration Files

- `phpunit.xml` - PHPUnit configuration
- `phpstan.neon` - PHPStan static analysis configuration
- `phpcs.xml` - PHP CodeSniffer coding standards configuration
- `composer.json` - Dependencies and scripts

## Writing Tests

Tests should be placed in the `tests/Unit/` directory, mirroring the structure of the `Api/` directory. All test classes should extend `PHPUnit\Framework\TestCase` and use proper namespacing.

Example test structure:
```php
<?php

declare(strict_types=1);

namespace Auctane\Api\Tests\Unit\Model;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testExample(): void
    {
        $this->assertTrue(true);
    }
}
```

## Mock Objects

Use the mock implementations in the `tests/Mock/` directory to simulate Magento dependencies. This allows tests to run without requiring a full Magento installation.

## Continuous Integration

The testing framework is designed to work with GitHub Actions and other CI/CD systems. Tests should complete within 5 minutes including setup time.