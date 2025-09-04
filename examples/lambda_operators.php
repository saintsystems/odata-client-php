<?php

/**
 * Lambda Operators Usage Examples
 * 
 * This file demonstrates how to use the new lambda operators (any/all) 
 * with the OData Client for PHP.
 */

require_once 'vendor/autoload.php';

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

// Initialize the OData client
$httpProvider = new GuzzleHttpProvider();
$client = new ODataClient('https://services.odata.org/V4/TripPinService', null, $httpProvider);

// Example 1: Find customers who have any completed orders
$customersWithCompletedOrders = $client->from('People')
    ->whereAny('Orders', function($query) {
        $query->where('Status', 'Completed');
    })
    ->get();

// Generates: People?$filter=Orders/any(o: o/Status eq 'Completed')

// Example 2: Find customers where all their orders are high-value
$customersWithAllHighValueOrders = $client->from('People')
    ->whereAll('Orders', function($query) {
        $query->where('Amount', '>', 100);
    })
    ->get();

// Generates: People?$filter=Orders/all(o: o/Amount gt 100)

// Example 3: Complex conditions with multiple criteria
$customersWithQualifiedOrders = $client->from('People')
    ->whereAny('Orders', function($query) {
        $query->where('Status', 'Completed')
              ->where('Amount', '>', 50);
    })
    ->get();

// Generates: People?$filter=Orders/any(o: o/Status eq 'Completed' and o/Amount gt 50)

// Example 4: Combining lambda operators with regular conditions
$activeCustomersWithOrders = $client->from('People')
    ->where('Status', 'Active')
    ->whereAny('Orders', function($query) {
        $query->where('Status', 'Pending');
    })
    ->get();

// Generates: People?$filter=Status eq 'Active' and Orders/any(o: o/Status eq 'Pending')

// Example 5: Using orWhereAny and orWhereAll
$flexibleCustomerQuery = $client->from('People')
    ->where('Status', 'Active')
    ->orWhereAny('Orders', function($query) {
        $query->where('Priority', 'High');
    })
    ->get();

// Generates: People?$filter=Status eq 'Active' or Orders/any(o: o/Priority eq 'High')

// Example 6: Nested conditions within lambda
$complexCustomerQuery = $client->from('People')
    ->whereAny('Orders', function($query) {
        $query->where(function($nested) {
            $nested->where('Status', 'Completed')
                   ->orWhere('Status', 'Shipped');
        })->where('Amount', '>', 100);
    })
    ->get();

// Generates: People?$filter=Orders/any(o: (o/Status eq 'Completed' or o/Status eq 'Shipped') and o/Amount gt 100)

// Example 7: Multiple lambda operators on different navigation properties
$qualifiedCustomers = $client->from('People')
    ->whereAny('Orders', function($query) {
        $query->where('Status', 'Completed');
    })
    ->whereAll('Reviews', function($query) {
        $query->where('Rating', '>=', 4);
    })
    ->get();

// Generates: People?$filter=Orders/any(o: o/Status eq 'Completed') and Reviews/all(r: r/Rating ge 4)

// Example 8: Lambda operators with select
$customerSummary = $client->from('People')
    ->select('FirstName', 'LastName', 'Email')
    ->whereAny('Orders', function($query) {
        $query->where('OrderDate', '>=', '2023-01-01');
    })
    ->get();

// Generates: People?$select=FirstName,LastName,Email&$filter=Orders/any(o: o/OrderDate ge '2023-01-01')

echo "Lambda operators are now available!\n";
echo "Check the examples above for usage patterns.\n";