<?php

require_once 'src/Core/helpers.php';
require_once 'src/Query/IGrammar.php';
require_once 'src/Query/Grammar.php';

use SaintSystems\OData\Query\Grammar;

// Test the datetime detection and preparation
$grammar = new Grammar();

// Use reflection to access the protected methods for testing
$reflection = new ReflectionClass($grammar);
$isUrlEncodedDateTime = $reflection->getMethod('isUrlEncodedDateTime');
$isUrlEncodedDateTime->setAccessible(true);
$isDateTime = $reflection->getMethod('isDateTime');
$isDateTime->setAccessible(true);
$prepareValue = $reflection->getMethod('prepareValue');
$prepareValue->setAccessible(true);

echo "Testing datetime detection and value preparation:\n\n";

// Test cases
$testCases = [
    // Regular strings (should be quoted)
    'hello' => "'hello'",
    'some text' => "'some text'",
    
    // Regular datetime (should not be quoted)
    '2000-02-05T08:48:36' => '2000-02-05T08:48:36',
    '2023-12-25T10:30:00+05:00' => '2023-12-25T10:30:00+05:00',
    '2023-12-25T10:30:00Z' => '2023-12-25T10:30:00Z',
    
    // URL-encoded datetime (should not be quoted)
    urlencode('2000-02-05T08:48:36+08:00') => '2000-02-05T08%3A48%3A36%2B08%3A00',
    urlencode('2023-03-05T08:48:36+08:00') => '2023-03-05T08%3A48%3A36%2B08%3A00',
    urlencode('2023-12-25T10:30:00Z') => '2023-12-25T10%3A30%3A00Z',
    
    // Numbers (should not be quoted)
    123 => 123,
    
    // Booleans (should be converted) - PHP considers 1 and 0 as integers, not booleans
    // true => 'true',
    // false => 'false',
];

foreach ($testCases as $input => $expected) {
    $result = $prepareValue->invoke($grammar, $input);
    $status = ($result === $expected) ? '✓ PASS' : '✗ FAIL';
    
    echo sprintf("Input: %-40s | Expected: %-40s | Got: %-40s | %s\n", 
        var_export($input, true), 
        var_export($expected, true), 
        var_export($result, true), 
        $status
    );
    
    if ($result !== $expected) {
        echo "  ERROR: Expected '$expected' but got '$result'\n";
    }
}

echo "\n\nTesting individual helper methods:\n";

// Test URL-encoded datetime detection
$urlEncodedTests = [
    urlencode('2000-02-05T08:48:36+08:00') => true,
    urlencode('2023-12-25T10:30:00Z') => true,
    '2000-02-05T08:48:36+08:00' => false, // Not URL encoded
    'hello' => false,
    urlencode('hello') => false, // URL encoded but not datetime
];

echo "\nURL-encoded datetime detection:\n";
foreach ($urlEncodedTests as $input => $expected) {
    $result = $isUrlEncodedDateTime->invoke($grammar, $input);
    $status = ($result === $expected) ? '✓ PASS' : '✗ FAIL';
    
    echo sprintf("Input: %-40s | Expected: %-8s | Got: %-8s | %s\n", 
        var_export($input, true), 
        var_export($expected, true), 
        var_export($result, true), 
        $status
    );
}

// Test regular datetime detection
$datetimeTests = [
    '2000-02-05T08:48:36+08:00' => true,
    '2023-12-25T10:30:00Z' => true,
    '2023-12-25T10:30:00' => true,
    'hello' => false,
    urlencode('2000-02-05T08:48:36+08:00') => false, // URL encoded
];

echo "\nRegular datetime detection:\n";
foreach ($datetimeTests as $input => $expected) {
    $result = $isDateTime->invoke($grammar, $input);
    $status = ($result === $expected) ? '✓ PASS' : '✗ FAIL';
    
    echo sprintf("Input: %-40s | Expected: %-8s | Got: %-8s | %s\n", 
        var_export($input, true), 
        var_export($expected, true), 
        var_export($result, true), 
        $status
    );
}

echo "\nTest completed!\n";