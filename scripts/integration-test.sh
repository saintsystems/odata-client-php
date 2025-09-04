#!/bin/bash

# Integration test script that runs tests against multiple PHP versions using Laravel Herd

# Array of PHP versions matching our GitHub Actions matrix
PHP_VERSIONS=("7.4" "8.0" "8.1" "8.2" "8.3" "8.4")

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "Starting integration tests across PHP versions..."
echo "=============================================="

# Track overall success
OVERALL_SUCCESS=true

# Iterate through each PHP version
for VERSION in "${PHP_VERSIONS[@]}"; do
    echo ""
    echo -e "${YELLOW}Testing PHP $VERSION${NC}"
    echo "------------------------------"
    
    # Switch PHP version using Herd
    echo "Switching to PHP $VERSION..."
    herd use php@$VERSION
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to switch to PHP $VERSION${NC}"
        OVERALL_SUCCESS=false
        continue
    fi
    
    # Verify PHP version
    CURRENT_VERSION=$(php -v | head -n 1)
    echo "Current PHP: $CURRENT_VERSION"
    
    # Update composer dependencies for this PHP version
    echo "Updating composer dependencies..."
    composer update --quiet
    
    if [ $? -ne 0 ]; then
        echo -e "${RED}Failed to update dependencies for PHP $VERSION${NC}"
        OVERALL_SUCCESS=false
        continue
    fi
    
    # Run tests
    echo "Running tests..."
    composer test
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Tests passed for PHP $VERSION${NC}"
    else
        echo -e "${RED}✗ Tests failed for PHP $VERSION${NC}"
        OVERALL_SUCCESS=false
    fi
done

echo ""
echo "=============================================="
if [ "$OVERALL_SUCCESS" = true ]; then
    echo -e "${GREEN}All integration tests passed!${NC}"
    exit 0
else
    echo -e "${RED}Some integration tests failed!${NC}"
    exit 1
fi