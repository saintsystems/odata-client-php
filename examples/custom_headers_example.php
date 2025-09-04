<?php

/**
 * Example: Using Custom Headers with OData Client
 * 
 * This example demonstrates how to add custom headers to OData requests
 * in the saintsystems/odata-client-php library.
 */

require_once '../vendor/autoload.php';

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

// Create an OData client
$httpProvider = new GuzzleHttpProvider();
$client = new ODataClient('https://services.odata.org/V4/TripPinService', null, $httpProvider);

// Example 1: Set multiple headers at once
$client->setHeaders([
    'X-Custom-Header' => 'MyCustomValue',
    'Authorization' => 'Bearer your-token-here',
    'X-Client-Version' => '1.0.0'
]);

// Example 2: Add a single header
$client->addHeader('X-Request-ID', uniqid());

// Example 3: Make a request with the custom headers
$people = $client->from('People')->get();

// Example 4: Use headers with the fluent query interface
$person = $client->from('People')
    ->withHeader('X-Query-Context', 'find-specific-person')
    ->withHeaders([
        'X-Debug' => 'true',
        'X-Performance-Track' => 'enabled'
    ])
    ->where('FirstName', 'Russell')
    ->first();

// Example 5: Different headers for different requests
$airlines = $client->from('Airlines')
    ->withHeader('X-Data-Source', 'airlines-api')
    ->get();

// The original client headers are preserved
$airports = $client->from('Airports')
    ->withHeader('X-Data-Source', 'airports-api')  // This only affects this request
    ->get();

echo "Custom headers have been successfully integrated!\n";
echo "You can now add custom headers to your OData requests in multiple ways:\n";
echo "1. Set headers on the client: \$client->setHeaders(\$headers)\n";
echo "2. Add single header to client: \$client->addHeader(\$name, \$value)\n";
echo "3. Add headers to specific query: \$client->from('Entity')->withHeader(\$name, \$value)\n";
echo "4. Add multiple headers to query: \$client->from('Entity')->withHeaders(\$headers)\n";