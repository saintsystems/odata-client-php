# Get started with the OData Client for PHP

A fluent library for calling OData REST services inspired by and based on the [Laravel Query Builder](https://laravel.com/docs/5.4/queries).

*This library is currently in preview. Please continue to provide [feedback](https://github.com/saintsystems/odata-client-php/issues/new) as we iterate towards a production-supported library.*

[![Build Status](https://img.shields.io/travis/saintsystems/odata-client-php/master.svg?style=flat-square)](https://travis-ci.org/saintsystems/odata-client-php)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/saintsystems/odata-client.svg?style=flat-square)](https://packagist.org/packages/saintsystems/odata-client)
[![Total Downloads](https://img.shields.io/packagist/dt/saintsystems/odata-client.svg?style=flat-square)](https://packagist.org/packages/saintsystems/odata-client)


## Install the SDK
You can install the PHP SDK with Composer.
```
composer require saintsystems/odata-client
```
### Call an OData Service

The following is an example that shows how to call an OData service.

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

use SaintSystems\OData\ODataClient;

class UsageExample
{
	public function __construct()
	{
		$odataServiceUrl = 'https://services.odata.org/V4/TripPinService';

		$odataClient = new ODataClient($odataServiceUrl);

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
