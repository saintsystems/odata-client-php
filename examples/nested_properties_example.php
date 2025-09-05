<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\Entity;

/**
 * Nested Property Access Example
 * 
 * This example demonstrates the various ways to access nested properties
 * in OData entities, including object-style access, dot notation, and
 * working with collections.
 */
class NestedPropertiesExample
{
    private $client;

    public function __construct()
    {
        // Initialize the OData client with TripPin service
        $httpProvider = new GuzzleHttpProvider();
        $this->client = new ODataClient('https://services.odata.org/V4/TripPinService', null, $httpProvider);
    }

    /**
     * Demonstrate object-style nested property access
     */
    public function objectStyleAccess()
    {
        echo "=== Object-Style Nested Property Access ===\n";
        
        try {
            // Get a person with address information
            $person = $this->client->from('People')->find('russellwhyte');
            
            echo "Person: {$person->FirstName} {$person->LastName}\n";
            
            // Access nested AddressInfo properties using object notation
            if ($person->AddressInfo && count($person->AddressInfo) > 0) {
                // Convert first address to Entity for object-style access
                $address = new Entity($person->AddressInfo[0]);
                
                echo "Primary Address:\n";
                echo "  Address: {$address->Address}\n";
                echo "  City: {$address->City}\n";
                echo "  Region: {$address->CountryRegion}\n";
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    /**
     * Demonstrate dot notation access for safe navigation
     */
    public function dotNotationAccess()
    {
        echo "=== Dot Notation Safe Navigation ===\n";
        
        try {
            $person = $this->client->from('People')->find('russellwhyte');
            
            echo "Person: {$person->FirstName} {$person->LastName}\n";
            
            // Safe navigation using dot notation
            $address = $person->getProperty('AddressInfo.0.Address');
            $city = $person->getProperty('AddressInfo.0.City');
            $region = $person->getProperty('AddressInfo.0.CountryRegion');
            
            echo "Using dot notation:\n";
            echo "  Address: " . ($address ?: 'Not available') . "\n";
            echo "  City: " . ($city ?: 'Not available') . "\n";
            echo "  Region: " . ($region ?: 'Not available') . "\n";
            
            // Try accessing a non-existent nested property
            $nonExistent = $person->getProperty('NonExistent.Property.Path');
            echo "  Non-existent property: " . ($nonExistent ?: 'null (safe)') . "\n";
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    /**
     * Demonstrate property existence checking
     */
    public function propertyExistenceChecking()
    {
        echo "=== Property Existence Checking ===\n";
        
        try {
            $person = $this->client->from('People')->find('russellwhyte');
            
            // Check if nested properties exist
            $hasAddress = $person->hasProperty('AddressInfo.0.Address');
            $hasCity = $person->hasProperty('AddressInfo.0.City');
            $hasNonExistent = $person->hasProperty('NonExistent.Property');
            
            echo "Property existence checks:\n";
            echo "  Has AddressInfo.0.Address: " . ($hasAddress ? 'Yes' : 'No') . "\n";
            echo "  Has AddressInfo.0.City: " . ($hasCity ? 'Yes' : 'No') . "\n";
            echo "  Has NonExistent.Property: " . ($hasNonExistent ? 'Yes' : 'No') . "\n";
            
            // Using isset() with object-style access
            if (isset($person->AddressInfo) && count($person->AddressInfo) > 0) {
                $address = new Entity($person->AddressInfo[0]);
                echo "  Address entity created successfully\n";
                
                if (isset($address->City)) {
                    echo "  City property exists on address entity\n";
                }
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }

    /**
     * Demonstrate working with collections in nested properties
     */
    public function workingWithCollections()
    {
        echo "=== Working with Collections ===\n";
        
        try {
            // Get multiple people with their address information
            $people = $this->client->select('UserName,FirstName,LastName,AddressInfo')
                                   ->from('People')
                                   ->top(3)
                                   ->get();
            
            foreach ($people as $person) {
                echo "Person: {$person->FirstName} {$person->LastName}\n";
                
                // AddressInfo remains as array for easy filtering and manipulation
                if ($person->AddressInfo && is_array($person->AddressInfo)) {
                    echo "  Addresses (" . count($person->AddressInfo) . "):\n";
                    
                    foreach ($person->AddressInfo as $index => $addressData) {
                        // Convert each address to Entity for object-style access
                        $address = new Entity($addressData);
                        
                        echo "    [{$index}] {$address->Address}, {$address->City}, {$address->CountryRegion}\n";
                    }
                } else {
                    echo "  No address information available\n";
                }
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Demonstrate expanded navigation properties with nested access
     */
    public function expandedNavigationProperties()
    {
        echo "=== Expanded Navigation Properties ===\n";
        
        try {
            // Get a person with their trips expanded
            $people = $this->client->from('People')
                                   ->expand('Trips')
                                   ->top(2)
                                   ->get();
            
            foreach ($people as $person) {
                echo "Person: {$person->FirstName} {$person->LastName}\n";
                
                if ($person->Trips && is_array($person->Trips)) {
                    echo "  Trips (" . count($person->Trips) . "):\n";
                    
                    foreach ($person->Trips as $tripData) {
                        // Convert trip to Entity for object-style access
                        $trip = new Entity($tripData);
                        
                        echo "    - {$trip->Name}";
                        
                        // Safe access to nested properties
                        if ($trip->hasProperty('Budget')) {
                            echo " (Budget: $" . $trip->getProperty('Budget') . ")";
                        }
                        
                        echo "\n";
                    }
                } else {
                    echo "  No trips available\n";
                }
                echo "\n";
            }
            
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Demonstrate complex nested data manipulation
     */
    public function complexDataManipulation()
    {
        echo "=== Complex Data Manipulation ===\n";
        
        // Create a mock complex entity (simulating ShareFile-style data)
        $mockData = [
            'Id' => 'folder-123',
            'Name' => 'Project Documents',
            'Info' => [
                'IsAHomeFolder' => false,
                'Description' => 'Project related documents',
                'Settings' => [
                    'AllowPublicSharing' => true,
                    'MaxFileSize' => 10485760, // 10MB
                    'Theme' => 'professional'
                ]
            ],
            'Children' => [
                ['Id' => 'file-1', 'Name' => 'Proposal.pdf', 'FileSizeBytes' => 2048000, 'Type' => 'File'],
                ['Id' => 'folder-2', 'Name' => 'Images', 'FileSizeBytes' => 0, 'Type' => 'Folder'],
                ['Id' => 'file-3', 'Name' => 'Budget.xlsx', 'FileSizeBytes' => 512000, 'Type' => 'File']
            ],
            'Creator' => [
                'Name' => 'John Doe',
                'Email' => 'john.doe@example.com'
            ]
        ];
        
        $entity = new Entity($mockData);
        
        echo "Folder: {$entity->Name}\n";
        echo "Creator: {$entity->Creator['Name']} ({$entity->Creator['Email']})\n";
        
        // Object-style access to nested Info
        echo "Is Home Folder: " . ($entity->Info->IsAHomeFolder ? 'Yes' : 'No') . "\n";
        echo "Description: {$entity->Info->Description}\n";
        
        // Deep nested access
        echo "Theme: {$entity->Info->Settings->Theme}\n";
        echo "Max File Size: " . number_format($entity->Info->Settings->MaxFileSize / 1024 / 1024, 1) . " MB\n";
        
        // Working with Children collection
        echo "\nChildren:\n";
        
        // Filter for folders (FileSizeBytes = 0)
        $folders = array_filter($entity->Children, function($child) {
            return $child['FileSizeBytes'] == 0;
        });
        
        // Filter for files
        $files = array_filter($entity->Children, function($child) {
            return $child['FileSizeBytes'] > 0;
        });
        
        echo "  Folders (" . count($folders) . "):\n";
        foreach ($folders as $folder) {
            echo "    - {$folder['Name']}\n";
        }
        
        echo "  Files (" . count($files) . "):\n";
        foreach ($files as $file) {
            $sizeKB = number_format($file['FileSizeBytes'] / 1024, 1);
            echo "    - {$file['Name']} ({$sizeKB} KB)\n";
        }
        
        echo "\n";
    }

    /**
     * Run all examples
     */
    public function runAll()
    {
        echo "OData Client - Nested Property Access Examples\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $this->objectStyleAccess();
        $this->dotNotationAccess();
        $this->propertyExistenceChecking();
        $this->workingWithCollections();
        $this->expandedNavigationProperties();
        $this->complexDataManipulation();
        
        echo "All examples completed!\n";
    }
}

// Run the examples
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $examples = new NestedPropertiesExample();
    $examples->runAll();
}