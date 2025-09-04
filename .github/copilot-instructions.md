# OData Client for PHP

OData Client for PHP is a fluent library for calling OData REST services, inspired by the Laravel Query Builder. This is a PHP library package (not an application) distributed via Composer/Packagist.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Prerequisites and Setup
- PHP 7.4 or higher required
- Composer required for dependency management
- Internet access required for dependency installation and testing (tests use external OData service)

### Bootstrap and Build Process
```bash
# Install production dependencies first (usually works within 2-3 minutes)
composer install --no-dev --no-interaction

# Then install dev dependencies (NEVER CANCEL: can take 10-20 minutes due to network issues)
composer install --no-interaction
```
**CRITICAL TIMING**: 
- Production dependencies: 2-3 minutes (usually successful)
- Full install with dev dependencies: 10-20 minutes due to GitHub API rate limits and authentication issues
- Set timeout to 30+ minutes for full installation
- NEVER CANCEL the installation process - timeouts are expected and Composer will retry with source downloads

### Running Tests
```bash
# First ensure all dependencies are installed
composer install --no-interaction

# Run all tests (NEVER CANCEL: takes 2-5 minutes due to external API calls)
vendor/bin/phpunit

# If PHPUnit is not available due to dependency installation issues:
php -f tests/ODataClientTest.php
```
**CRITICAL TIMING**: Tests make real HTTP calls to external OData service (services.odata.org/V4/TripPinService). Set timeout to 10+ minutes. NEVER CANCEL test execution.

**DEPENDENCY NOTE**: If dev dependencies fail to install due to network timeouts, you can still validate basic functionality with production dependencies only.

### Code Quality and Validation
```bash
# Static analysis (if PHPStan is available)
vendor/bin/phpstan analyse src/

# Syntax check all PHP files
find src/ -name "*.php" -exec php -l {} \;

# Check autoloading
composer dump-autoload
```

## Validation Scenarios

After making changes to this library, ALWAYS test the following scenarios:

### Core Functionality Validation
```php
<?php
require_once 'vendor/autoload.php';

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

// Basic validation script (works with production dependencies only):
echo "Testing class loading...\n";
echo "✓ ODataClient: " . (class_exists('SaintSystems\OData\ODataClient') ? 'Found' : 'NOT FOUND') . "\n";
echo "✓ Entity: " . (class_exists('SaintSystems\OData\Entity') ? 'Found' : 'NOT FOUND') . "\n";
echo "✓ Query\\Builder: " . (class_exists('SaintSystems\OData\Query\Builder') ? 'Found' : 'NOT FOUND') . "\n";

// If Guzzle is available, test HTTP provider:
if (class_exists('GuzzleHttp\Client')) {
    $httpProvider = new GuzzleHttpProvider();
    $client = new ODataClient('https://services.odata.org/V4/TripPinService', null, $httpProvider);
    echo "✓ Basic client instantiation successful\n";
    
    // Test simple query (requires internet access)
    try {
        $people = $client->from('People')->get();
        echo "✓ Query execution successful, found " . $people->count() . " people\n";
    } catch (Exception $e) {
        echo "✗ Query failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠ Guzzle not available - HTTP provider testing skipped\n";
}
```

### Integration Test Scenarios
Run the existing test suite which covers:
- Entity set queries with various filters
- Pagination and cursor-based iteration  
- String contains/not-contains operations
- Skip token handling for large datasets
- Query building with where/orWhere clauses

## Build and Test Timing Expectations

- **Composer install (production only)**: 2-3 minutes  
- **Composer install (with dev dependencies)**: 10-20 minutes (NEVER CANCEL - network timeouts are common)
- **PHPUnit test suite**: 2-5 minutes (NEVER CANCEL - makes external HTTP calls)
- **Static analysis**: 1-2 minutes (if dependencies available)
- **Syntax validation** (`find src/ -name "*.php" -exec php -l {} \;`): ~1.5 seconds (34 files)
- **Autoload regeneration** (`composer dump-autoload`): <1 second

## Common Issues and Workarounds

### Dependency Installation Issues
- **Problem**: "Could not authenticate against github.com" during composer install
- **Solution**: This is expected due to GitHub API rate limits. Composer will fallback to source installations. Wait for completion.

- **Problem**: Network timeouts during dependency download
- **Solution**: Increase timeout values. Installation via source (git clone) takes longer but works reliably.

### Testing Issues  
- **Problem**: Test failures due to external service unavailability
- **Solution**: The test suite depends on services.odata.org being accessible. If tests fail due to network issues, this is environmental, not code-related.

### Development Dependencies
```bash
# If dev dependencies fail to install, try production-only first:
composer install --no-dev --no-interaction

# For testing without full dev environment:
# 1. Basic functionality test
composer dump-autoload
php -l src/ODataClient.php

# 2. Manual test execution (if PHPUnit unavailable)
php -f tests/ODataClientTest.php

# 3. Alternative: Install specific dev packages individually  
composer require --dev phpunit/phpunit --no-interaction
composer require --dev phpstan/phpstan --no-interaction

# NOTE: Individual package installation may also timeout, but has higher success rate
```

## Key Project Structure

### Source Code (`src/`)
- `ODataClient.php` - Main client class
- `GuzzleHttpProvider.php` - HTTP provider implementation using Guzzle
- `Psr17HttpProvider.php` - PSR-17/PSR-18 HTTP provider implementation
- `Query/` - Query builder classes
- `Core/` - Helper functions and utilities

### Tests (`tests/`)
- `ODataClientTest.php` - Main integration tests
- `Query/BuilderTest.php` - Query builder unit tests
- `Core/HelpersTest.php` - Helper function tests

### Configuration Files
- `composer.json` - Dependencies and package configuration
- `phpunit.xml` - Test suite configuration
- `.github/workflows/ci.yml` - CI pipeline (tests PHP 7.4-8.4)

## HTTP Provider Configuration

This library requires an HTTP provider to be explicitly configured:

### Using Guzzle (Recommended)
```php
use SaintSystems\OData\GuzzleHttpProvider;
$httpProvider = new GuzzleHttpProvider();
```

### Using PSR-17/PSR-18
```php
use SaintSystems\OData\Psr17HttpProvider;
$httpProvider = new Psr17HttpProvider($httpClient, $requestFactory, $streamFactory);
```

## CI/CD Pipeline Validation

The GitHub Actions workflow (`.github/workflows/ci.yml`) runs:
1. PHP version matrix testing (7.4, 8.0, 8.1, 8.2, 8.3, 8.4)
2. Composer dependency installation
3. PHPUnit test execution

Before committing changes, ensure your code passes these same steps locally.

## Example Usage Patterns

The library supports Laravel-style query building:

```php
// Basic queries
$people = $client->from('People')->get();
$person = $client->from('People')->find('russellwhyte');

// Filtering
$filtered = $client->from('People')->where('FirstName', 'Russell')->get();
$multiple = $client->from('People')
    ->where('FirstName', 'Russell')
    ->orWhere('LastName', 'Ketchum')
    ->get();

// Selection and pagination
$limited = $client->select('FirstName', 'LastName')
    ->from('People')
    ->pageSize(5)
    ->get();

// Cursor-based iteration for large datasets
$cursor = $client->from('People')->pageSize(8)->cursor();
$cursor->each(function($person) {
    echo $person->FirstName . "\n";
});
```

Always test these patterns when making changes to query building or HTTP handling code.