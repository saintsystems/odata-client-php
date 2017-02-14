# Get started with the OData Client for PHP

A fluent library for calling OData REST services inspired by and based on the [Laravel Query Builder](https://laravel.com/docs/5.4/queries).

*This library is currently in preview. Please continue to provide [feedback](https://github.com/saintsystems/odata-client-php/issues/new) as we iterate towards a production-supported library.*

[![Build Status](https://travis-ci.org/saintsystems/odata-client-php.svg?branch=master)](https://travis-ci.org/saintsystems/odata-client-php)


## Install the SDK
You can install the PHP SDK with Composer.
```
{
    "require": {
        "saintsystems/odata-client": "0.1.*"
    }
}
```
### Call an OData Service

The following is an example that shows how to call an OData service.

```php
use SaintSystems\OData;

class UsageExample
{
    $odataServiceUrl = 'http://services.odata.org/V4/TripPinService';

    $odataClient = new ODataClient($odataServiceUrl);

    // Retrieve all entities from the "People" Entity Set
    $people = $odataClient->from('People')->get();

    // Or retrieve a specific entity by the Entity ID/Key
    $person = $odataClient->from('People')->find('russellwhyte');
    echo "Hello, I am $person->FirstName ";

    // Want to only select a few properties/columns?
    $people = $odataClient->from('People')->select('FirstName','LastName')->get();
}
```

## Develop

### Run Tests

Run ```vendor/bin/phpunit``` from the base directory.


## Documentation and resources

* [Documentation](https://github.com/saintsystems/dynamics-sdk-php/blob/master/docs/index.html)

* [Wiki](https://github.com/saintsystems/dynamics-sdk-php/wiki)

* [Examples](https://github.com/saintsystems/dynamics-sdk-php/wiki/Example-calls)

* [OData website](http://www.odata.org)

* [OASIS OData Version 4.0 Documentation](http://docs.oasis-open.org/odata/odata/v4.0/odata-v4.0-part1-protocol.html)

## Issues

View or log issues on the [Issues](https://github.com/saintsystems/odata-client-php/issues) tab in the repo.

## Copyright and license

Copyright (c) Saint Systems, LLC. All Rights Reserved. Licensed under the MIT [license](LICENSE).
