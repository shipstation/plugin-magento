# Code Coverage Validation

This document describes the comprehensive code coverage validation system implemented for the Auctane_Api module.

## Overview

The coverage validation system ensures that all critical components meet minimum coverage requirements:

- **Models**: 80% minimum coverage
- **Controllers**: 85% minimum coverage  
- **Exceptions**: 100% minimum coverage
- **Overall Project**: 80% minimum coverage

## Components

### 1. Coverage Validation Script

**Location**: `scripts/validate-coverage.php`

The main validation script that:
- Parses PHPUnit clover coverage reports
- Validates coverage against configurable thresholds
- Provides detailed reporting with class-level breakdown
- Supports different environments (development, staging, production)
- Generates JSON reports for CI/CD integration

**Usage**:
```bash
# Basic validation
php scripts/validate-coverage.php coverage/clover.xml coverage/coverage-report.json

# Environment-specific validation
COVERAGE_ENV=production php scripts/validate-coverage.php coverage/clover.xml coverage/coverage-report.json
```

### 2. Configuration

**Location**: `coverage-config.json`

Configurable thresholds for different environments:

```json
{
  "thresholds": {
    "overall": 80.0,
    "models": 80.0,
    "controllers": 85.0,
    "exceptions": 100.0
  },
  "environments": {
    "development": { "thresholds": { "overall": 70.0, ... } },
    "staging": { "thresholds": { "overall": 75.0, ... } },
    "production": { "thresholds": { "overall": 80.0, ... } }
  }
}
```

### 3. PHPUnit Integration

**Location**: `phpunit.xml`

PHPUnit is configured with:
- Coverage thresholds that fail tests on insufficient coverage
- Multiple output formats (HTML, XML, Clover, Cobertura)
- Proper source inclusion/exclusion rules

### 4. Composer Scripts

Available scripts for coverage validation:

```bash
# Run tests with coverage and validate
composer test-coverage-xml
composer validate-coverage

# Environment-specific validation
composer validate-coverage-dev      # Development thresholds
composer validate-coverage-strict   # Production thresholds

# Comprehensive coverage check
composer coverage-check             # Full validation with strict thresholds
```

### 5. GitHub Actions Integration

**Location**: `.github/workflows/tests.yml`

The CI pipeline:
- Runs tests with coverage across multiple PHP versions
- Validates coverage thresholds automatically
- Fails builds on insufficient coverage
- Uploads coverage reports as artifacts
- Posts coverage summaries on pull requests

### 6. Coverage Validation Tests

**Location**: `tests/Coverage/`

Comprehensive test suite that validates:
- Individual class coverage requirements
- Component-level coverage aggregation
- Coverage validation script functionality
- Test completeness and naming conventions
- Critical method coverage

## Usage

### Local Development

1. **Run tests with coverage**:
   ```bash
   composer test-coverage
   ```

2. **Validate coverage**:
   ```bash
   composer validate-coverage
   ```

3. **Generate comprehensive report**:
   ```bash
   composer coverage-report
   ```

### CI/CD Pipeline

The coverage validation runs automatically in GitHub Actions:

1. Tests execute with coverage collection
2. Coverage validation script runs with production thresholds
3. Build fails if coverage is insufficient
4. Coverage reports are uploaded as artifacts

### Coverage Requirements

#### Model Classes (80% minimum)
- `Auctane\Api\Model\Authorization`
- `Auctane\Api\Model\Action\Export`
- `Auctane\Api\Model\Action\ShipNotify`
- `Auctane\Api\Model\ApiKeyGenerator`
- `Auctane\Api\Model\Check`
- `Auctane\Api\Model\ConfigureShipstation`
- `Auctane\Api\Model\Weight`
- `Auctane\Api\Model\WeightAdapter`

#### Controller Classes (85% minimum)
- `Auctane\Api\Controller\BaseController`
- `Auctane\Api\Controller\BaseAuthorizedController`
- `Auctane\Api\Controller\Diagnostics\Live`
- `Auctane\Api\Controller\Diagnostics\Version`
- `Auctane\Api\Controller\InventoryFetch\Index`
- `Auctane\Api\Controller\SalesOrdersExport\Index`
- `Auctane\Api\Controller\ShipmentNotification\Index`

#### Exception Classes (100% minimum)
- `Auctane\Api\Exception\ApiException`
- `Auctane\Api\Exception\AuthenticationFailedException`
- `Auctane\Api\Exception\AuthorizationException`
- `Auctane\Api\Exception\BadRequestException`
- `Auctane\Api\Exception\InvalidXmlException`
- `Auctane\Api\Exception\NotFoundException`

## Output Examples

### Successful Validation
```
📊 Code Coverage Validation Report
==================================

Overall Coverage:
✅ PASS: Overall coverage 85.2% meets threshold 80%

Component Coverage:
✅ PASS: models coverage 82.1% meets threshold 80%
✅ PASS: controllers coverage 87.3% meets threshold 85%
✅ PASS: exceptions coverage 100% meets threshold 100%
```

### Failed Validation
```
📊 Code Coverage Validation Report
==================================

Overall Coverage:
❌ FAIL: Overall coverage 75.2% is below threshold 80%

Component Coverage:
❌ FAIL: models coverage 72.1% is below threshold 80%
✅ PASS: controllers coverage 87.3% meets threshold 85%
❌ FAIL: exceptions coverage 85% is below threshold 100%

Detailed Class Coverage (for failed components):

models classes:
  ❌ Auctane\Api\Model\Authorization: 65%
  ✅ Auctane\Api\Model\Action\Export: 85%
  ❌ Auctane\Api\Model\Action\ShipNotify: 70%
```

## Troubleshooting

### Coverage Not Generated
Ensure Xdebug or PCOV is installed:
```bash
composer install-coverage
```

### Tests Failing Due to Coverage
1. Check which classes are below threshold
2. Add more unit tests for uncovered code
3. Review if code can be simplified or refactored

### CI Pipeline Failures
1. Check the coverage validation step output
2. Review the uploaded coverage artifacts
3. Ensure all critical classes have corresponding tests

## Integration with Development Workflow

1. **Before committing**: Run `composer coverage-check`
2. **During PR review**: Check coverage report comments
3. **After merging**: Monitor coverage trends in CI artifacts

This system ensures consistent code quality and helps maintain high test coverage across the entire codebase.