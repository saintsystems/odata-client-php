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

### Nested Property Access

The OData Client provides powerful support for accessing nested properties in OData entities, making it easy to work with complex data structures returned by modern OData services.

#### Object-Style Access

Access nested properties naturally using object notation:

```php
<?php

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

$httpProvider = new GuzzleHttpProvider();
$client = new ODataClient('https://services.odata.org/V4/TripPinService', null, $httpProvider);

// Get a person with address information
$person = $client->from('People')->find('russellwhyte');

// Access nested properties directly
$city = $person->AddressInfo[0]->City;           // Object-style access
$country = $person->AddressInfo[0]->CountryRegion; // Deep nesting supported

// Complex nested structures work naturally
if ($person->Settings && $person->Settings->Preferences) {
    $theme = $person->Settings->Preferences->Theme;
}
```

#### Dot Notation Access

Use dot notation for safe navigation through nested properties:

```php
// Safe access with dot notation - returns null if any part doesn't exist
$city = $person->getProperty('AddressInfo.0.City');
$country = $person->getProperty('AddressInfo.0.CountryRegion');
$theme = $person->getProperty('Settings.Preferences.Theme');

// Works with array indices and object properties
$firstFriendName = $person->getProperty('Friends.0.FirstName');
$homeAddress = $person->getProperty('AddressInfo.0.Address');
```

#### Property Existence Checking

Check if nested properties exist before accessing them:

```php
// Check existence using hasProperty()
if ($person->hasProperty('AddressInfo.0.City')) {
    $city = $person->getProperty('AddressInfo.0.City');
}

// Also works with isset() for object-style access
if (isset($person->AddressInfo[0]->City)) {
    $city = $person->AddressInfo[0]->City;
}

// Check for deeply nested paths
if ($person->hasProperty('Settings.Preferences.AutoSave')) {
    $autoSave = $person->getProperty('Settings.Preferences.AutoSave');
}
```

#### Working with Collections

Handle arrays and collections within nested structures:

```php
// Get people with address information
$people = $client->select('UserName,FirstName,LastName,AddressInfo')
                 ->from('People')
                 ->get();

foreach ($people as $person) {
    echo "Person: " . $person->FirstName . " " . $person->LastName . "\n";
    
    // Access nested address info - remains as array for easy filtering
    $addresses = $person->AddressInfo;
    
    // Filter addresses by type
    $homeAddresses = array_filter($addresses, function($address) {
        return isset($address['Type']) && $address['Type'] === 'Home';
    });
    
    // Access properties within filtered results
    foreach ($homeAddresses as $address) {
        // Convert to Entity for object-style access
        $addrEntity = new \SaintSystems\OData\Entity($address);
        echo "  Home Address: " . $addrEntity->Address . ", " . $addrEntity->City . "\n";
    }
}
```

#### Real-World ShareFile OData Example

Working with ShareFile-style OData responses with Info objects and Children collections:

```php
// Query for folders with nested Info and Children data
$folders = $client->select('Id,Name,CreatorNameShort,Info,Info/IsAHomeFolder,Children/Id,Children/Name')
                  ->from('Items')
                  ->where('HasChildren', true)
                  ->get();

foreach ($folders as $folder) {
    echo "Folder: " . $folder->Name . "\n";
    echo "Creator: " . $folder->CreatorNameShort . "\n";
    
    // Access nested Info properties
    if ($folder->Info) {
        echo "Is Home Folder: " . ($folder->Info->IsAHomeFolder ? 'Yes' : 'No') . "\n";
        
        // Safe navigation for optional nested properties
        if ($folder->hasProperty('Info.Settings.Theme')) {
            echo "Theme: " . $folder->getProperty('Info.Settings.Theme') . "\n";
        }
    }
    
    // Work with Children collection
    if ($folder->Children) {
        echo "Children:\n";
        
        // Filter children by type
        $subfolders = array_filter($folder->Children, function($child) {
            return $child['FileSizeBytes'] == 0; // Folders have 0 file size
        });
        
        foreach ($subfolders as $subfolder) {
            echo "  - " . $subfolder['Name'] . " (ID: " . $subfolder['Id'] . ")\n";
        }
    }
    echo "\n";
}
```

#### Integration with Query Building

Nested property access works seamlessly with OData query operations:

```php
// Select specific nested properties
$result = $client->select('Id,Name,Info/IsAHomeFolder,Children/Name,AddressInfo/City')
                 ->from('Items')
                 ->get();

// Use in where clauses (if supported by the OData service)
$homeItems = $client->from('Items')
                    ->where('Info/IsAHomeFolder', true)
                    ->get();

// Expand related data and access nested properties
$peopleWithTrips = $client->from('People')
                          ->expand('Trips')
                          ->get();

foreach ($peopleWithTrips as $person) {
    foreach ($person->Trips as $trip) {
        // Access nested trip properties
        $tripEntity = new \SaintSystems\OData\Entity($trip);
        echo $person->FirstName . " has trip: " . $tripEntity->Name . "\n";
    }
}
```

**Key Features:**
- **Multiple Access Patterns**: Object notation, dot notation, and array access all supported
- **Automatic Type Conversion**: Nested associative arrays become Entity objects for object-style access
- **Safe Navigation**: Non-existent properties return `null` instead of throwing errors
- **Performance Optimized**: Entity objects created lazily only when accessed
- **Backward Compatible**: All existing code continues to work unchanged
- **Collection Friendly**: Arrays remain as arrays for easy filtering and manipulation

For comprehensive examples and advanced usage patterns, see [`examples/nested_properties_example.php`](examples/nested_properties_example.php).

### Lambda Operators (any/all)

The OData Client supports lambda operators `any` and `all` for filtering collections within entities. These operators allow you to filter based on conditions within related navigation properties.

#### Basic Usage

```php
<?php

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

$httpProvider = new GuzzleHttpProvider();
$client = new ODataClient('https://services.odata.org/V4/TripPinService', null, $httpProvider);

// Find people who have any completed trips
$peopleWithCompletedTrips = $client->from('People')
    ->whereAny('Trips', function($query) {
        $query->where('Status', 'Completed');
    })
    ->get();
// Generates: People?$filter=Trips/any(t: t/Status eq 'Completed')

// Find people where all their trips are high-budget
$peopleWithAllHighBudgetTrips = $client->from('People')
    ->whereAll('Trips', function($query) {
        $query->where('Budget', '>', 1000);
    })
    ->get();
// Generates: People?$filter=Trips/all(t: t/Budget gt 1000)
```

#### Available Lambda Methods

- `whereAny($navigationProperty, $callback)` - Returns true if any element matches the condition
- `whereAll($navigationProperty, $callback)` - Returns true if all elements match the condition  
- `orWhereAny($navigationProperty, $callback)` - OR version of whereAny
- `orWhereAll($navigationProperty, $callback)` - OR version of whereAll

#### Complex Conditions

```php
// Multiple conditions within lambda
$peopleWithQualifiedTrips = $client->from('People')
    ->whereAny('Trips', function($query) {
        $query->where('Status', 'Completed')
              ->where('Budget', '>', 500);
    })
    ->get();
// Generates: People?$filter=Trips/any(t: t/Status eq 'Completed' and t/Budget gt 500)

// Combining with regular conditions
$activePeopleWithTrips = $client->from('People')
    ->where('Status', 'Active')
    ->whereAny('Trips', function($query) {
        $query->where('Status', 'Pending');
    })
    ->get();
// Generates: People?$filter=Status eq 'Active' and Trips/any(t: t/Status eq 'Pending')
```

**Key Features:**
- **Automatic variable generation**: Uses first letter of navigation property (e.g., `Trips` → `t`)
- **Full operator support**: Supports all comparison operators (eq, ne, gt, ge, lt, le)
- **Nested conditions**: Handles complex where clauses within lambda expressions
- **Fluent interface**: Works seamlessly with other query builder methods

For comprehensive examples and advanced usage patterns, see [`examples/lambda_operators.php`](examples/lambda_operators.php).

### Batch Operations

The OData Client supports batch operations, which allow you to send multiple HTTP requests in a single batch request. This can significantly improve performance when you need to perform multiple operations.

#### Basic Batch Usage

```php
<?php

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

$httpProvider = new GuzzleHttpProvider();
$client = new ODataClient('https://services.odata.org/V4/TripPinService', null, $httpProvider);

// Simple batch with multiple GET requests
$response = $client->batch()
    ->get('People', 'get-people')
    ->get('Airlines', 'get-airlines') 
    ->get('Airports', 'get-airports')
    ->execute();
```

#### Changesets for Atomic Operations

Changesets ensure that all operations within the changeset are executed atomically (all succeed or all fail):

```php
// Batch with changeset for atomic operations
$response = $client->batch()
    ->startChangeset()
        ->post('People', [
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'UserName' => 'johndoe',
            'Emails' => ['john.doe@example.com']
        ], 'create-person')
        ->patch('People(\'russellwhyte\')', [
            'FirstName' => 'Jane',
            'LastName' => 'Smith'
        ], 'update-person')
    ->endChangeset()
    ->execute();
```

#### Mixed Batch Operations

You can combine individual requests and changesets in a single batch:

```php
$response = $client->batch()
    // Individual queries (not in changeset)
    ->get('People?$top=5', 'get-top-people')
    
    // Atomic operations in changeset
    ->startChangeset()
        ->post('People', $newPersonData, 'create-person')
        ->delete('People(\'obsolete-id\')', 'delete-person')
    ->endChangeset()
    
    // More individual queries
    ->get('Airlines?$top=3', 'get-airlines')
    ->execute();
```

#### Available Batch Methods

- `get($uri, $id)` - Add a GET request to the batch
- `post($uri, $data, $id)` - Add a POST request to the batch
- `put($uri, $data, $id)` - Add a PUT request to the batch
- `patch($uri, $data, $id)` - Add a PATCH request to the batch
- `delete($uri, $id)` - Add a DELETE request to the batch
- `startChangeset()` - Begin a new changeset for atomic operations
- `endChangeset()` - End the current changeset
- `execute()` - Execute the batch request

**Key Features:**
- **Performance**: Reduces network overhead by combining multiple requests
- **Atomic transactions**: Changesets ensure all-or-nothing execution
- **Content-ID references**: Use request IDs to reference results between operations
- **Error handling**: Individual operation errors are reported separately
- **Fluent interface**: Chain operations for clean, readable code

For comprehensive examples, see [`examples/batch_operations.php`](examples/batch_operations.php).

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
