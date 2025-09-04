<?php

require_once 'vendor/autoload.php';

// Test to reproduce the large number issue
echo "Testing large number handling...\n";

// Test PHP's json_decode behavior with large numbers
$jsonWithLargeNumber = '{"id": 80000000000000000000000, "name": "test"}';
echo "Original JSON: $jsonWithLargeNumber\n";

// Default json_decode behavior
$decoded = json_decode($jsonWithLargeNumber, true);
echo "json_decode() result: id = " . var_export($decoded['id'], true) . "\n";

// With JSON_BIGINT_AS_STRING flag
$decodedBigInt = json_decode($jsonWithLargeNumber, true, 512, JSON_BIGINT_AS_STRING);
echo "json_decode() with JSON_BIGINT_AS_STRING: id = " . var_export($decodedBigInt['id'], true) . "\n";

echo "\n";
echo "Classes: " . (class_exists('SaintSystems\OData\ODataClient') ? '✓' : '✗') . " ODataClient\n";
echo "Autoload: " . (file_exists('vendor/autoload.php') ? '✓' : '✗') . " Working\n";

// Test ODataResponse behavior
use SaintSystems\OData\ODataResponse;

$mockRequest = new stdClass();
$mockRequest->test = true;

$response = new ODataResponse($mockRequest, $jsonWithLargeNumber, 200, []);
echo "ODataResponse->getBody(): " . var_export($response->getBody(), true) . "\n";