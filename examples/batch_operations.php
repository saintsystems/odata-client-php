<?php

/**
 * OData Batch Operations Examples
 * 
 * This example demonstrates how to use batch operations with the OData Client for PHP.
 * Batch operations allow you to send multiple requests in a single HTTP request,
 * which can significantly improve performance when making multiple OData operations.
 */

require_once '../vendor/autoload.php';

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;

// Initialize the OData client
$httpProvider = new GuzzleHttpProvider();
$client = new ODataClient('https://services.odata.org/V4/TripPinService', null, $httpProvider);

echo "=== OData Batch Operations Examples ===\n\n";

// Example 1: Simple Batch with Multiple GET Requests
echo "1. Simple Batch with Multiple GET Requests\n";
echo "--------------------------------------------\n";

try {
    $response = $client->batch()
        ->get('People', 'get-people')
        ->get('Airlines', 'get-airlines') 
        ->get('Airports', 'get-airports')
        ->execute();
        
    echo "✓ Batch with multiple GET requests executed successfully\n";
    echo "Response contains multiple parts for different entity sets\n\n";
} catch (Exception $e) {
    echo "✗ Error in simple batch: " . $e->getMessage() . "\n\n";
}

// Example 2: Batch with Changeset (Atomic Operations)
echo "2. Batch with Changeset for Atomic Operations\n";
echo "----------------------------------------------\n";

try {
    $newPerson = [
        'FirstName' => 'John',
        'LastName' => 'Doe',
        'UserName' => 'johndoe' . time(),
        'Emails' => ['john.doe@example.com']
    ];
    
    $updatedPerson = [
        'FirstName' => 'Jane',
        'LastName' => 'Smith'
    ];
    
    $response = $client->batch()
        ->startChangeset()
            ->post('People', $newPerson, 'create-person')
            ->patch('People(\'russellwhyte\')', $updatedPerson, 'update-person')
        ->endChangeset()
        ->execute();
        
    echo "✓ Batch with changeset executed successfully\n";
    echo "All operations in changeset will be committed atomically\n\n";
} catch (Exception $e) {
    echo "✗ Error in changeset batch: " . $e->getMessage() . "\n\n";
}

// Example 3: Mixed Batch - Queries and Changesets Combined
echo "3. Mixed Batch - Queries and Changesets Combined\n";
echo "------------------------------------------------\n";

try {
    $response = $client->batch()
        // First, get some data (not in changeset)
        ->get('People?$top=5', 'get-top-people')
        
        // Then perform atomic operations
        ->startChangeset()
            ->post('People', [
                'FirstName' => 'Alice',
                'LastName' => 'Johnson',
                'UserName' => 'alicejohnson' . time(),
                'Emails' => ['alice.johnson@example.com']
            ], 'create-alice')
            ->post('People', [
                'FirstName' => 'Bob',
                'LastName' => 'Wilson',
                'UserName' => 'bobwilson' . time(),
                'Emails' => ['bob.wilson@example.com']
            ], 'create-bob')
        ->endChangeset()
        
        // More queries outside changeset
        ->get('Airlines?$top=3', 'get-top-airlines')
        ->execute();
        
    echo "✓ Mixed batch with queries and changesets executed successfully\n";
    echo "Queries executed independently, changesets executed atomically\n\n";
} catch (Exception $e) {
    echo "✗ Error in mixed batch: " . $e->getMessage() . "\n\n";
}

// Example 4: Batch with Different HTTP Methods
echo "4. Batch with Different HTTP Methods\n";
echo "------------------------------------\n";

try {
    $response = $client->batch()
        ->get('People?$filter=FirstName eq \'Russell\'', 'find-russell')
        ->startChangeset()
            ->put('People(\'russellwhyte\')', [
                'FirstName' => 'Russell',
                'LastName' => 'Whyte',
                'UserName' => 'russellwhyte',
                'Emails' => ['russell@example.com'],
                'AddressInfo' => []
            ], 'replace-russell')
            ->delete('People(\'vincentcalabrese\')', 'delete-vincent')
        ->endChangeset()
        ->execute();
        
    echo "✓ Batch with mixed HTTP methods executed successfully\n";
    echo "Demonstrated GET, PUT, and DELETE operations\n\n";
} catch (Exception $e) {
    echo "✗ Error in mixed methods batch: " . $e->getMessage() . "\n\n";
}

// Example 5: Error Handling in Batch Operations
echo "5. Error Handling in Batch Operations\n";
echo "--------------------------------------\n";

try {
    // This batch intentionally includes operations that might fail
    $response = $client->batch()
        ->get('People', 'valid-request')
        ->get('NonExistentEntitySet', 'invalid-request')  // This will likely fail
        ->startChangeset()
            ->post('People', [
                'FirstName' => 'Test',
                'UserName' => 'test' . time()
                // Missing required fields - might cause validation errors
            ], 'potentially-failing-create')
        ->endChangeset()
        ->execute();
        
    echo "✓ Batch with potential errors handled gracefully\n";
    echo "Check response for individual operation status codes\n\n";
} catch (Exception $e) {
    echo "✗ Expected error in batch with invalid operations: " . $e->getMessage() . "\n";
    echo "This demonstrates how batch errors are handled\n\n";
}

// Example 6: Advanced Batch with Content-ID References
echo "6. Advanced Batch with Content-ID References\n";
echo "---------------------------------------------\n";

try {
    $response = $client->batch()
        ->startChangeset()
            ->post('People', [
                'FirstName' => 'Reference',
                'LastName' => 'Example',
                'UserName' => 'refexample' . time(),
                'Emails' => ['ref@example.com']
            ], 'new-person')
            // Note: In a real scenario, you might reference the created person
            // in subsequent operations using the Content-ID
        ->endChangeset()
        ->get('People?$orderby=FirstName desc&$top=1', 'get-latest-person')
        ->execute();
        
    echo "✓ Advanced batch with Content-ID references executed\n";
    echo "Content-IDs allow referencing results from one operation in another\n\n";
} catch (Exception $e) {
    echo "✗ Error in advanced batch: " . $e->getMessage() . "\n\n";
}

echo "=== Batch Operations Examples Complete ===\n\n";

echo "Key Concepts Demonstrated:\n";
echo "- Simple batching of multiple GET requests\n";
echo "- Changesets for atomic operations (all succeed or all fail)\n";
echo "- Mixing queries and changesets in a single batch\n";
echo "- Using different HTTP methods (GET, POST, PUT, PATCH, DELETE)\n";
echo "- Error handling in batch operations\n";
echo "- Content-ID references for operation dependencies\n\n";

echo "Benefits of Batch Operations:\n";
echo "- Reduced network overhead (single HTTP request)\n";
echo "- Atomic transactions for related operations\n";
echo "- Better performance for multiple operations\n";
echo "- Simplified error handling for grouped operations\n\n";

echo "Best Practices:\n";
echo "- Use changesets for operations that should be atomic\n";
echo "- Keep batch sizes reasonable (typically 10-100 operations)\n";
echo "- Use Content-IDs for operations that depend on each other\n";
echo "- Handle both individual operation errors and batch-level errors\n";