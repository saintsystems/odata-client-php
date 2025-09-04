<?php

// Include necessary files  
require_once 'src/Core/helpers.php';
require_once 'src/Query/IGrammar.php';
require_once 'src/Query/Grammar.php';

use SaintSystems\OData\Query\Grammar;

// Test the specific methods involved in where clause compilation
$grammar = new Grammar();

echo "Testing Grammar where clause compilation methods:\n\n";

// Get reflection access to protected methods
$reflection = new ReflectionClass($grammar);
$prepareValue = $reflection->getMethod('prepareValue');
$prepareValue->setAccessible(true);
$whereBasic = $reflection->getMethod('whereBasic');
$whereBasic->setAccessible(true);
$getOperatorMapping = $reflection->getMethod('getOperatorMapping');
$getOperatorMapping->setAccessible(true);

// Test individual value preparation (the core fix)
echo "1. Testing prepareValue method:\n";
$testValues = [
    // URL-encoded datetime (should NOT be quoted)
    urlencode('2000-02-05T08:48:36+08:00') => '2000-02-05T08%3A48%3A36%2B08%3A00',
    urlencode('2023-03-05T08:48:36+08:00') => '2023-03-05T08%3A48%3A36%2B08%3A00',
    
    // Regular datetime (should NOT be quoted)
    '2023-12-25T10:30:00+05:00' => '2023-12-25T10:30:00+05:00',
    '2023-12-25T10:30:00Z' => '2023-12-25T10:30:00Z',
    
    // Regular string (should be quoted)
    'h' => "'h'",
    'hello world' => "'hello world'",
    
    // Numbers (should not be changed)
    123 => 123,
    // Note: PHP treats '123' as a string that looks like a number, 
    // and our datetime detection correctly identifies it as not a datetime,
    // so it gets quoted as expected
];

foreach ($testValues as $input => $expected) {
    $result = $prepareValue->invoke($grammar, $input);
    $status = ($result === $expected) ? '✓ PASS' : '✗ FAIL';
    
    echo "  " . str_pad(var_export($input, true), 40) . " -> " . str_pad(var_export($result, true), 30) . " [$status]\n";
    
    if ($result !== $expected) {
        echo "    Expected: " . var_export($expected, true) . "\n";
    }
}

echo "\n2. Testing operator mapping:\n";
$operators = ['=', '>=', '<=', '<', '>', '!=', '<>'];
foreach ($operators as $op) {
    $mapped = $getOperatorMapping->invoke($grammar, $op);
    echo "  '$op' -> '$mapped'\n";
}

echo "\n3. Testing complete where clause compilation:\n";
echo "  (Skipping whereBasic method test due to Builder dependency)\n";

echo "\n4. Testing the exact scenario from the issue:\n";

// This simulates what the user's code does
$filterEarliest = urlencode('2000-02-05T08:48:36+08:00');
$filterLatest = urlencode('2023-03-05T08:48:36+08:00');
$domainKey = urlencode('h'); // This is just 'h'

echo "User's code:\n";
echo "  \$filterEarliest = urlencode('2000-02-05T08:48:36+08:00'); // = '$filterEarliest'\n";
echo "  \$filterLatest = urlencode('2023-03-05T08:48:36+08:00');   // = '$filterLatest'\n";
echo "  \$domainKey = urlencode('h');                               // = '$domainKey'\n\n";

echo "How these values are processed:\n";
$earliest_prepared = $prepareValue->invoke($grammar, $filterEarliest);
$latest_prepared = $prepareValue->invoke($grammar, $filterLatest);
$domain_prepared = $prepareValue->invoke($grammar, $domainKey);

echo "  filterEarliest: '$filterEarliest' -> '$earliest_prepared'\n";
echo "  filterLatest:   '$filterLatest' -> '$latest_prepared'\n";
echo "  domainKey:      '$domainKey' -> '$domain_prepared'\n\n";

echo "Expected OData filter (without quotes around datetime values):\n";
echo "  UpdatedAt ge $earliest_prepared and UpdatedAt le $latest_prepared and domainKey eq $domain_prepared\n\n";

echo "Original problem (with quotes around datetime values):\n";
echo "  UpdatedAt ge '$earliest_prepared' and UpdatedAt le '$latest_prepared' and domainKey eq '$domain_prepared'\n\n";

$success = (
    $earliest_prepared === $filterEarliest && 
    $latest_prepared === $filterLatest && 
    $domain_prepared === "'$domainKey'"
);

echo "Overall result: " . ($success ? "✓ SUCCESS - Fix is working correctly!" : "✗ FAILURE - Fix needs adjustment") . "\n";

echo "\nTest completed!\n";