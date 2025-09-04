<?php

// Include necessary files
require_once 'src/Core/helpers.php';
require_once 'src/Query/IGrammar.php';
require_once 'src/Query/Grammar.php';
require_once 'src/Query/IProcessor.php';
require_once 'src/Query/Processor.php';
require_once 'src/IODataClient.php';
require_once 'src/Query/Builder.php';

use SaintSystems\OData\Query\Grammar;
use SaintSystems\OData\Query\Processor;
use SaintSystems\OData\Query\Builder;

// Mock client for testing
class MockODataClient implements SaintSystems\OData\IODataClient {
    public function getQueryGrammar() { return new Grammar(); }
    public function getPostProcessor() { return new Processor(); }
    public function getBaseUrl() { return 'https://test.com/'; }
    public function setEntityKey($key) { return $this; }
    public function getPageSize() { return null; }
    public function setPageSize($size) { return $this; }
    public function getEntityKey() { return null; }
    // ... other required methods (not used in this test)
    public function get($uri, $bindings = []) { return null; }
    public function post($uri, $data) { return null; }
    public function patch($uri, $data) { return null; }
    public function delete($uri) { return null; }
    public function cursor($uri, $bindings = []) { return null; }
    public function request($method, $uri, $body = null) { return null; }
    public function getAuthenticationProvider() { return null; }
    public function setBaseUrl($url) { return $this; }
    public function getHttpProvider() { return null; }
    public function setHttpProvider($provider) { return $this; }
    public function setEntityReturnType($type) { return $this; }
}

// Test the Builder with datetime values
$client = new MockODataClient();
$builder = new Builder($client);

echo "Testing OData Builder with datetime values:\n\n";

// Test 1: Regular datetime
$builder1 = new Builder($client);
$uri1 = $builder1->from('ProductSearch')
    ->where('UpdatedAt', '>=', '2000-02-05T08:48:36+08:00')
    ->toRequest();

echo "Test 1 - Regular datetime:\n";
echo "URI: $uri1\n";
$expected1 = 'ProductSearch?$filter=UpdatedAt ge 2000-02-05T08:48:36+08:00';
echo "Expected: $expected1\n";
echo "Result: " . ($uri1 === $expected1 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 2: URL-encoded datetime (the main issue)
$builder2 = new Builder($client);
$filterEarliest = urlencode('2000-02-05T08:48:36+08:00');
$filterLatest = urlencode('2023-03-05T08:48:36+08:00');

$uri2 = $builder2->from('ProductSearch')
    ->where('UpdatedAt', '>=', $filterEarliest)
    ->where('UpdatedAt', '<=', $filterLatest)
    ->toRequest();

echo "Test 2 - URL-encoded datetime (main issue):\n";
echo "URI: $uri2\n";
$expected2 = 'ProductSearch?$filter=UpdatedAt ge 2000-02-05T08%3A48%3A36%2B08%3A00 and UpdatedAt le 2023-03-05T08%3A48%3A36%2B08%3A00';
echo "Expected: $expected2\n";
echo "Result: " . ($uri2 === $expected2 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 3: Mixed query (datetime + other conditions)
$builder3 = new Builder($client);
$domainKey = urlencode('h');
$uri3 = $builder3->from('ProductSearch')
    ->where('UpdatedAt', '>=', urlencode('2000-02-05T08:48:36+08:00'))
    ->where('UpdatedAt', '<=', urlencode('2023-03-05T08:48:36+08:00'))
    ->where('domainKey', 'eq', $domainKey)
    ->expand(['activeSubstances', 'atcCodes', 'doseForms', 'rms'])
    ->skip(0)
    ->take(10)
    ->toRequest();

echo "Test 3 - Mixed query (reproducing original issue):\n";
echo "URI: $uri3\n";
// Note: domainKey with urlencode('h') = 'h' (no change), so it should be quoted
$expected3 = 'ProductSearch?$filter=UpdatedAt ge 2000-02-05T08%3A48%3A36%2B08%3A00 and UpdatedAt le 2023-03-05T08%3A48%3A36%2B08%3A00 and domainKey eq \'h\'&$expand=activeSubstances,atcCodes,doseForms,rms&$skip=0&$top=10';
echo "Expected: $expected3\n";
echo "Result: " . ($uri3 === $expected3 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 4: Regular string (should still be quoted)
$builder4 = new Builder($client);
$uri4 = $builder4->from('People')
    ->where('FirstName', '=', 'Russell')
    ->toRequest();

echo "Test 4 - Regular string (should still be quoted):\n";
echo "URI: $uri4\n";
$expected4 = 'People?$filter=FirstName eq \'Russell\'';
echo "Expected: $expected4\n";
echo "Result: " . ($uri4 === $expected4 ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 5: Various datetime formats
$datetimeFormats = [
    '2023-12-25T10:30:00',
    '2023-12-25T10:30:00+05:00',
    '2023-12-25T10:30:00Z',
    urlencode('2023-12-25T10:30:00+05:00'),
    urlencode('2023-12-25T10:30:00Z'),
];

echo "Test 5 - Various datetime formats:\n";
foreach ($datetimeFormats as $i => $format) {
    $builder = new Builder($client);
    $uri = $builder->from('Events')->where('EventDate', '>=', $format)->toRequest();
    
    // URL-encoded values should remain encoded, others should remain as-is
    $expectedValue = $format;
    $expected = "Events?\$filter=EventDate ge $expectedValue";
    
    echo "  Format " . ($i + 1) . ": $format\n";
    echo "  URI: $uri\n";
    echo "  Expected: $expected\n";
    echo "  Result: " . ($uri === $expected ? "✓ PASS" : "✗ FAIL") . "\n\n";
}

echo "All tests completed!\n";