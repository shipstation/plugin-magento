# Code Coverage Setup

This project is configured for comprehensive code coverage reporting using PHPUnit. The coverage configuration supports multiple output formats and includes quality thresholds.

## Prerequisites

To generate code coverage reports, you need to install a PHP code coverage driver:

### Option 1: Xdebug (Recommended for development)
```bash
# Install via PECL
pecl install xdebug

# Or via package manager (macOS with Homebrew)
brew install php-xdebug

# Or via package manager (Ubuntu/Debian)
sudo apt-get install php-xdebug
```

### Option 2: PCOV (Faster for CI/CD)
```bash
# Install via PECL
pecl install pcov

# Enable in php.ini
echo "extension=pcov.so" >> /path/to/php.ini
```

## Coverage Commands

### Generate HTML Coverage Report
```bash
composer test-coverage
```
This generates an interactive HTML report in `coverage/html/index.html`

### Generate XML Coverage Reports
```bash
composer test-coverage-xml
```
This generates XML reports in `coverage/xml/` and Clover format in `coverage/clover.xml`

### CI/CD Coverage Reports
```bash
composer test-coverage-ci
```
This generates Clover and Cobertura XML reports suitable for CI/CD systems

### Check Coverage Installation
```bash
composer install-coverage
```
This displays installation instructions for coverage drivers

## Coverage Configuration

The PHPUnit configuration (`phpunit.xml`) includes:

- **HTML Reports**: Interactive browsable coverage reports
- **XML Reports**: Machine-readable coverage data
- **Clover Reports**: For CI/CD integration (Jenkins, GitHub Actions, etc.)
- **Cobertura Reports**: For GitLab CI and other systems
- **CRAP4J Reports**: Code complexity and coverage analysis
- **Text Reports**: Console output and file-based summaries

## Coverage Targets

The project aims for:
- **Minimum 60%** overall coverage from entry points
- **Minimum 70%** coverage from class methods
- **80%+ coverage** for Model classes (business logic)
- **85%+ coverage** for Controller classes
- **100% coverage** for Exception classes

## Coverage Exclusions

The following are excluded from coverage analysis:
- View templates (`Api/view/`)
- Translation files (`Api/i18n/`)
- Registration files (`Api/registration.php`)
- Vendor dependencies
- Test files themselves

## Integration with CI/CD

### GitHub Actions
```yaml
- name: Run tests with coverage
  run: composer test-coverage-ci

- name: Upload coverage to Codecov
  uses: codecov/codecov-action@v3
  with:
    file: coverage/clover.xml
```

### GitLab CI
```yaml
test:
  script:
    - composer test-coverage-ci
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
  artifacts:
    reports:
      coverage_report:
        coverage_format: cobertura
        path: coverage/cobertura.xml
```

## Troubleshooting

### "No code coverage driver available"
Install Xdebug or PCOV as described in the Prerequisites section.

### Low coverage warnings
Check the HTML report to identify uncovered code paths and add appropriate tests.

### Memory issues during coverage
Increase PHP memory limit:
```bash
php -d memory_limit=1G vendor/bin/phpunit --coverage-html coverage/html
```

### Slow coverage generation
Consider using PCOV instead of Xdebug for faster coverage collection:
```bash
php -d extension=pcov.so vendor/bin/phpunit --coverage-html coverage/html
```