#!/bin/bash

# Detect PHPUnit version and use appropriate configuration file

# Get PHPUnit version
PHPUNIT_VERSION=$(vendor/bin/phpunit --version | grep -oP 'PHPUnit \K[0-9]+' || echo "0")

# Choose config file based on version
if [ "$PHPUNIT_VERSION" -ge "10" ]; then
    CONFIG_FILE="phpunit10.xml"
elif [ "$PHPUNIT_VERSION" -ge "9" ]; then
    CONFIG_FILE="phpunit9.xml"
else
    # Fall back to phpunit9.xml for older versions
    CONFIG_FILE="phpunit9.xml"
fi

# Run PHPUnit with the appropriate config
vendor/bin/phpunit -c "$CONFIG_FILE" "$@"