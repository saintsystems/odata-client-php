# Get started with the OData Client for PHP

A fluent library for calling OData REST services inspired by and based on the [Laravel Query Builder](https://laravel.com/docs/5.4/queries).

*This library is currently in preview. Please continue to provide [feedback](https://github.com/saintsystems/odata-client-php/issues/new) as we iterate towards a production-supported library.*

[![Build Status](https://github.com/saintsystems/odata-client-php/actions/workflows/ci.yml/badge.svg)](https://github.com/saintsystems/odata-client-php/actions/workflows/ci.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/saintsystems/odata-client.svg?style=flat-square)](https://packagist.org/packages/saintsystems/odata-client)
[![Total Downloads](https://img.shields.io/packagist/dt/saintsystems/odata-client.svg?style=flat-square)](https://packagist.org/packages/saintsystems/odata-client)

For WordPress users, please see our [Gravity Forms Dynamics 365 Add-On](https://www.saintsystems.com/products/gravity-forms-dynamics-crm-add-on/).

## Install the SDK
You can install the PHP SDK with Composer.
```
composer require saintsystems/odata-client
```

### HTTP Provider Configuration

Starting from version 0.10.0, the OData Client requires an HTTP provider to be explicitly set. This allows you to use any HTTP client implementation that suits your needs.

#### Using Guzzle (recommended for most users)

First, install Guzzle:
```bash
composer require guzzlehttp/guzzle
```

Then configure the OData client:
```php
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

$httpProvider = new GuzzleHttpProvider();
$odataClient = new ODataClient($odataServiceUrl, null, $httpProvider);
```

#### Using PSR-17/PSR-18 implementations

You can also use any PSR-17/PSR-18 compatible HTTP client:

```php
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\Psr17HttpProvider;

// Example using Symfony HTTP Client with Nyholm PSR-7
$httpClient = new \Symfony\Component\HttpClient\Psr18Client();
$requestFactory = new \Nyholm\Psr7\Factory\Psr17Factory();
$streamFactory = new \Nyholm\Psr7\Factory\Psr17Factory();

$httpProvider = new Psr17HttpProvider($httpClient, $requestFactory, $streamFactory);
$odataClient = new ODataClient($odataServiceUrl, null, $httpProvider);
```

### Call an OData Service

The following is an example that shows how to call an OData service.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

class UsageExample
{
	public function __construct()
	{
		$odataServiceUrl = 'https://services.odata.org/V4/TripPinService';

		$httpProvider = new GuzzleHttpProvider();
		$odataClient = new ODataClient($odataServiceUrl, null, $httpProvider);

		// Retrieve all entities from the "People" Entity Set
		$people = $odataClient->from('People')->get();

		// Or retrieve a specific entity by the Entity ID/Key
		try {
			$person = $odataClient->from('People')->find('russellwhyte');
			echo "Hello, I am $person->FirstName ";
		} catch (Exception $e) {
			echo $e->getMessage();
		}

		// Want to only select a few properties/columns?
		$people = $odataClient->from('People')->select('FirstName','LastName')->get();
	}
}

$example = new UsageExample();
```

## Advanced Usage

### Custom Headers

You can add custom headers to your OData requests for authentication, tracking, or other purposes:

```php
<?php

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

$httpProvider = new GuzzleHttpProvider();
$odataClient = new ODataClient($odataServiceUrl, null, $httpProvider);

// Method 1: Set headers on the client (applies to all requests)
$odataClient->setHeaders([
    'Authorization' => 'Bearer your-token-here',
    'X-Custom-Header' => 'MyCustomValue',
    'X-Client-Version' => '1.0.0'
]);

// Method 2: Add a single header to the client
$odataClient->addHeader('X-Request-ID', uniqid());

// Method 3: Add headers to specific queries using the fluent interface
$people = $odataClient->from('People')
    ->withHeader('X-Query-Context', 'get-all-people')
    ->withHeaders([
        'X-Debug' => 'true',
        'X-Performance-Track' => 'enabled'
    ])
    ->get();

// Headers set on the client persist across requests
$person = $odataClient->from('People')->find('russellwhyte');

// Query-specific headers only apply to that request
$airlines = $odataClient->from('Airlines')
    ->withHeader('X-Data-Source', 'airlines-api')
    ->get();
```

### Custom Query Options

You can add custom query parameters to your OData requests that are not part of the standard OData specification. This is useful for passing additional parameters to your OData service:

```php
<?php

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

$httpProvider = new GuzzleHttpProvider();
$odataClient = new ODataClient($odataServiceUrl, null, $httpProvider);

// Method 1: Add custom options using string format
$people = $odataClient->from('People')
    ->addOption('timeout=30')
    ->addOption('format=minimal')
    ->get();
// Results in: /People?timeout=30&format=minimal

// Method 2: Add custom options using array format  
$people = $odataClient->from('People')
    ->addOption(['timeout' => '30', 'debug' => 'true'])
    ->get();
// Results in: /People?timeout=30&debug=true

// Method 3: Mix with standard OData parameters
$people = $odataClient->from('People')
    ->select('FirstName', 'LastName')
    ->where('FirstName', 'Russell')
    ->addOption('version=2.0')
    ->get();
// Results in: /People?$select=FirstName,LastName&$filter=FirstName eq 'Russell'&version=2.0

// Method 4: Multiple addOption calls are merged (not overwritten)
$people = $odataClient->from('People')
    ->addOption('timeout=30')
    ->addOption('format=minimal')
    ->addOption(['debug' => 'true']);
// Results in: /People?timeout=30&format=minimal&debug=true

// Custom option keys are validated:
// ✓ Valid: 'timeout', 'custom_param', 'kebab-case', 'camelCase'
// ✗ Invalid: '$reserved' (starts with $), 'invalid key!' (special chars)
```

**Key Features:**
- **Merging**: Multiple `addOption()` calls merge instead of overwriting
- **Flexible**: Supports both string (`'key=value'`) and array (`['key' => 'value']`) formats
- **Validated**: Custom option keys are validated to prevent conflicts with OData system parameters
- **URL Encoded**: Special characters in keys and values are automatically URL encoded
- **Fluent**: Chain with other query methods for clean, readable code

### Custom Timeout Configuration

If you need to configure custom network timeouts for your OData requests, you can create a subclass of `ODataClient` and override the `createRequest` method:

```php
<?php

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

class CustomTimeoutODataClient extends ODataClient {
    private $customTimeout;
    
    public function __construct($baseUrl, $authProvider = null, $httpProvider = null, $timeout = 30) {
        parent::__construct($baseUrl, $authProvider, $httpProvider);
        $this->customTimeout = $timeout;
    }
    
    protected function createRequest($method, $requestUri) {
        $request = parent::createRequest($method, $requestUri);
        $request->setTimeout($this->customTimeout);
        return $request;
    }
}

// Usage with custom timeout
$httpProvider = new GuzzleHttpProvider();
$client = new CustomTimeoutODataClient('https://api.example.com/odata', null, $httpProvider, 60);
$result = $client->from('Products')->get(); // Uses 60-second timeout
```

This approach allows you to customize request creation without having to override the entire request flow, following the template method pattern.

**Key Features:**
- **Optional**: Headers are completely optional - existing code works without changes
- **Flexible**: Set headers on the client or per-query
- **Fluent**: Chain header methods with other query methods
- **Preserved**: Default OData headers are automatically included
- **Isolated**: Query-specific headers don't affect the client's global headers

For a complete working example, see [`examples/custom_headers_example.php`](examples/custom_headers_example.php).

## Develop

### Run Tests

Run ```vendor/bin/phpunit``` from the base directory.


## Documentation and resources

* [Documentation](https://github.com/saintsystems/odata-client-php/wiki/Example-Calls)

* [Wiki](https://github.com/saintsystems/odata-client-php/wiki)

* [Examples](https://github.com/saintsystems/odata-client-php/wiki/Example-calls)

* [OData website](http://www.odata.org)

* [OASIS OData Version 4.0 Documentation](http://docs.oasis-open.org/odata/odata/v4.0/odata-v4.0-part1-protocol.html)

## Issues

View or log issues on the [Issues](https://github.com/saintsystems/odata-client-php/issues) tab in the repo.

## Copyright and license

Copyright (c) Saint Systems, LLC. All Rights Reserved. Licensed under the MIT [license](LICENSE).
