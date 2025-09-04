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
