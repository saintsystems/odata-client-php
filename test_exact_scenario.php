<?php

// Include necessary files  
require_once 'src/Core/helpers.php';
require_once 'src/Query/IGrammar.php';
require_once 'src/Query/Grammar.php';

use SaintSystems\OData\Query\Grammar;

// Create a mock Builder object for testing
class MockBuilder {
    public $entitySet = 'ProductSearch';
    public $entityKey = null;
    public $wheres = [];
    public $properties = null;
    public $expands = ['activeSubstances', 'atcCodes', 'doseForms', 'rms'];
    public $orders = null;
    public $customOption = null;
    public $skip = 0;
    public $skiptoken = null;
    public $take = 10;
    public $totalCount = null;
    public $count = null;
    public $queryString = '?';
    
    public function __construct() {
        // Simulate the where conditions from the original issue
        $filterEarliest = urlencode('2000-02-05T08:48:36+08:00');
        $filterLatest = urlencode('2023-03-05T08:48:36+08:00');
        $domainKey = urlencode('h'); // This becomes just 'h'
        
        $this->wheres = [
            [
                'type' => 'Basic',
                'column' => 'UpdatedAt',
                'operator' => '>=',
                'value' => $filterEarliest,
                'boolean' => 'and'
            ],
            [
                'type' => 'Basic', 
                'column' => 'UpdatedAt',
                'operator' => '<=',
                'value' => $filterLatest,
                'boolean' => 'and'
            ],
            [
                'type' => 'Basic',
                'column' => 'domainKey', 
                'operator' => '=',
                'value' => $domainKey,
                'boolean' => 'and'
            ]
        ];
    }
}

// Test the Grammar compilation
$grammar = new Grammar();
$builder = new MockBuilder();

echo "Testing OData Grammar with the exact issue scenario:\n\n";

// Test the complete compilation
$compiledUri = $grammar->compileSelect($builder);

echo "Compiled URI:\n";
echo "$compiledUri\n\n";

// What we should get (the desired result)
$expectedUri = 'ProductSearch?$filter=UpdatedAt ge 2000-02-05T08%3A48%3A36%2B08%3A00 and UpdatedAt le 2023-03-05T08%3A48%3A36%2B08%3A00 and domainKey eq \'h\'&$expand=activeSubstances,atcCodes,doseForms,rms&$skip=0&$top=10';

echo "Expected URI:\n";
echo "$expectedUri\n\n";

// What the original issue was generating (the problem)
$problemUri = 'ProductSearch?$filter=UpdatedAt ge \'2000-02-05T08%3A48%3A36%2B08%3A00\' and UpdatedAt le \'2023-03-05T08%3A48%3A36%2B08%3A00\' and domainKey eq \'h\'&$expand=activeSubstances,atcCodes,doseForms,rms&$skip=0&$top=10';

echo "Original problem URI (with quotes around datetimes):\n";
echo "$problemUri\n\n";

// Check results
if ($compiledUri === $expectedUri) {
    echo "✓ SUCCESS: The fix works correctly!\n";
    echo "  - URL-encoded datetime values are NOT wrapped in quotes\n";
    echo "  - Regular string values (like domainKey 'h') are still quoted\n";
    echo "  - All other query components work as expected\n";
} else {
    echo "✗ ISSUE: The result doesn't match expectations\n";
    echo "  Analyzing differences...\n";
    
    // Find differences
    $expectedParts = explode('&', $expectedUri);
    $actualParts = explode('&', $compiledUri);
    
    echo "\n  Expected parts:\n";
    foreach ($expectedParts as $i => $part) {
        echo "    $i: $part\n";
    }
    
    echo "\n  Actual parts:\n";
    foreach ($actualParts as $i => $part) {
        echo "    $i: $part\n";
    }
}

echo "\n";

// Test individual value preparation to verify our fix
echo "Testing individual value preparation:\n";

$reflection = new ReflectionClass($grammar);
$prepareValue = $reflection->getMethod('prepareValue');
$prepareValue->setAccessible(true);

$testValues = [
    urlencode('2000-02-05T08:48:36+08:00') => '2000-02-05T08%3A48%3A36%2B08%3A00',
    urlencode('2023-03-05T08:48:36+08:00') => '2023-03-05T08%3A48%3A36%2B08%3A00', 
    'h' => "'h'",  // Regular string should be quoted
    '2023-12-25T10:30:00+05:00' => '2023-12-25T10:30:00+05:00', // Regular datetime should not be quoted
];

foreach ($testValues as $input => $expected) {
    $result = $prepareValue->invoke($grammar, $input);
    $status = ($result === $expected) ? '✓ PASS' : '✗ FAIL';
    
    echo "  Input: " . var_export($input, true) . "\n";
    echo "  Expected: " . var_export($expected, true) . "\n";
    echo "  Got: " . var_export($result, true) . "\n";
    echo "  Status: $status\n\n";
}

echo "Test completed!\n";