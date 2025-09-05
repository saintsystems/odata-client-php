<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\Entity;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;
use Illuminate\Support\LazyCollection;
use SaintSystems\OData\Constants;
use SaintSystems\OData\RequestHeader;

class ODataClientTest extends TestCase
{
    private $baseUrl;

    public function setUp(): void
    {
        $this->baseUrl = 'https://services.odata.org/V4/TripPinService';
    }

    private function createODataClient($authenticationProvider = null)
    {
        $httpProvider = new GuzzleHttpProvider();
        $client = new ODataClient($this->baseUrl, $authenticationProvider, $httpProvider);
        return $client;
    }

    public function testODataClientConstructor()
    {
        $odataClient = $this->createODataClient();
        $this->assertNotNull($odataClient);
        $baseUrl = $odataClient->getBaseUrl();
        $this->assertEquals('https://services.odata.org/V4/TripPinService/', $baseUrl);
    }

    public function testODataClientEntitySetQuery()
    {
        $odataClient = $this->createODataClient();
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')->get();
        $this->assertTrue(is_array($people->toArray()));
    }

    public function testODataClientEntitySetQueryWithSelect()
    {
        $odataClient = $this->createODataClient();
        $this->assertNotNull($odataClient);
        $people = $odataClient->select('FirstName','LastName')->from('People')->get();
        $this->assertTrue(is_array($people->toArray()));
    }

    public function testODataClientFromQueryWithWhere()
    {
        $odataClient = $this->createODataClient();
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')->where('FirstName','Russell')->get();
        $this->assertTrue(is_array($people->toArray()));
        $this->assertEquals(1, $people->count());
    }

    public function testODataClientFromQueryWithWhereOrWhere()
    {
        $odataClient = $this->createODataClient();
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')
                              ->where('FirstName','Russell')
                              ->orWhere('LastName','Ketchum')
                              ->get();
        // dd($people);
        $this->assertTrue(is_array($people->toArray()));
        $this->assertEquals(2, $people->count());
    }

    public function testODataClientFromQueryWithWhereOrWhereArrays()
    {
        $odataClient = $this->createODataClient();
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')
                              ->where([
                                ['FirstName', 'Russell'],
                                ['LastName', 'Whyte'],
                              ])
                              ->orWhere([
                                ['FirstName', 'Scott'],
                                ['LastName', 'Ketchum'],
                              ])
                              ->get();
        $this->assertTrue(is_array($people->toArray()));
        $this->assertEquals(2, $people->count());
    }

    public function testODataClientFromQueryWithWhereOrWhereArraysAndOperators()
    {
        $odataClient = $this->createODataClient();
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')
                              ->where([
                                ['FirstName', '=', 'Russell'],
                                ['LastName', '=', 'Whyte'],
                              ])
                              ->orWhere([
                                ['FirstName', '=', 'Scott'],
                                ['LastName', '=', 'Ketchum'],
                              ])
                              ->get();
        $this->assertTrue(is_array($people->toArray()));
        $this->assertEquals(2, $people->count());
    }

    public function testODataClientFind()
    {
        $odataClient = $this->createODataClient();
        $this->assertNotNull($odataClient);
        $person = $odataClient->from('People')->find('russellwhyte');
        $this->assertEquals('russellwhyte', $person->UserName);
    }

    public function testODataClientSkipToken()
    {
        $pageSize = 8;
        $odataClient = $this->createODataClient(function($request) use($pageSize) {
            $request->headers[RequestHeader::PREFER] = Constants::ODATA_MAX_PAGE_SIZE . '=' . $pageSize;
        });
        $this->assertNotNull($odataClient);
        $odataClient->setEntityReturnType(false);
        $page1response = $odataClient->from('People')->get()->first();
        $page1results = collect($page1response->getResponseAsObject(Entity::class));
        $this->assertEquals($pageSize, $page1results->count());

        $page1skiptoken = $page1response->getSkipToken();
        if ($page1skiptoken) {
            $page2response = $odataClient->from('People')->skiptoken($page1skiptoken)->get()->first();
            $page2results = collect($page2response->getResponseAsObject(Entity::class));
            $page2skiptoken = $page2response->getSkipToken();
            $this->assertEquals($pageSize, $page2results->count());
        }

        $lastPageSize = 4;
        if ($page2skiptoken) {
            $page3response = $odataClient->from('People')->skiptoken($page2skiptoken)->get()->first();
            $page3results = collect($page3response->getResponseAsObject(Entity::class));
            $page3skiptoken = $page3response->getSkipToken();
            $this->assertEquals($lastPageSize, $page3results->count());
            $this->assertNull($page3skiptoken);
        }
    }

    public function testODataClientCursorBeLazyCollection()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $this->assertInstanceOf(LazyCollection::class, $data);
    }

    public function testODataClientCursorCountShouldEqualTotalEntitySetCount()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $expectedCount = 20;

        $this->assertEquals($expectedCount, $data->count());
    }

    public function testODataClientCursorToArrayCountShouldEqualPageSize()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $this->assertEquals($pageSize, count($data->toArray()));
    }

    public function testODataClientCursorFirstShouldReturnEntityRussellWhyte()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $first = $data->first();
        $this->assertInstanceOf(Entity::class, $first);
        $this->assertEquals('russellwhyte', $first->UserName);
    }

    public function testODataClientCursorLastShouldReturnEntityKristaKemp()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $last = $data->last();
        $this->assertInstanceOf(Entity::class, $last);
        $this->assertEquals('kristakemp', $last->UserName);
    }

    public function testODataClientCursorSkip1FirstShouldReturnEntityScottKetchum()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $second = $data->skip(1)->first();
        $this->assertInstanceOf(Entity::class, $second);
        $this->assertEquals('scottketchum', $second->UserName);
    }

    public function testODataClientCursorSkip4FirstShouldReturnEntityWillieAshmore()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $fifth = $data->skip(4)->first();
        $this->assertInstanceOf(Entity::class, $fifth);
        $this->assertEquals('willieashmore', $fifth->UserName);
    }

    public function testODataClientCursorSkip7FirstShouldReturnEntityKeithPinckney()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $eighth = $data->skip(7)->first();
        $this->assertInstanceOf(Entity::class, $eighth);
        $this->assertEquals('keithpinckney', $eighth->UserName);
    }

    public function testODataClientCursorSkip8FirstShouldReturnEntityMarshallGaray()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $ninth = $data->skip(8)->first();
        $this->assertInstanceOf(Entity::class, $ninth);
        $this->assertEquals('marshallgaray', $ninth->UserName);
    }

    public function testODataClientCursorSkip16FirstShouldReturnEntitySandyOsbord()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $seventeenth = $data->skip(16)->first();
        $this->assertInstanceOf(Entity::class, $seventeenth);
        $this->assertEquals('sandyosborn', $seventeenth->UserName);
    }

    public function testODataClientCursorSkip16LastPageShouldBe4Records()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $lastPage = $data->skip(16);
        $lastPageSize = 4;
        $this->assertEquals($lastPageSize, count($lastPage->toArray()));
    }

    public function testODataClientCursorIteratingShouldReturnAll20Entities()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $expectedCount = 20;
        $counter = 0;

        $data->each(function ($person) use(&$counter) {
            $counter++;
            $this->assertInstanceOf(Entity::class, $person);
        });

        $this->assertEquals($expectedCount, $counter);
    }

    public function testODataClientCursorPageSizeOf20ShouldReturnAllEntities()
    {
        $odataClient = $this->createODataClient();

        $pageSize = 20;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $this->assertEquals($pageSize, count($data->toArray()));
    }

    public function testODataClientWhereContainsRealData()
    {
        $odataClient = $this->createODataClient();

        // Test whereContains - should find airlines with "Airline" in their name
        $airlines = $odataClient->from('Airlines')
            ->whereContains('Name', 'Airline')
            ->get();

        $this->assertTrue(is_array($airlines->toArray()));
        $this->assertGreaterThan(0, $airlines->count());
        
        // Verify all results contain "Airline" in the name
        foreach ($airlines as $airline) {
            $this->assertStringContainsString('Airline', $airline->Name);
        }

        // Should find American Airlines, Shanghai Airline, and Austrian Airlines
        $airlineCodes = $airlines->pluck('AirlineCode')->toArray();
        $this->assertContains('AA', $airlineCodes); // American Airlines
        $this->assertContains('FM', $airlineCodes); // Shanghai Airline
        $this->assertContains('OS', $airlineCodes); // Austrian Airlines
    }

    public function testODataClientOrWhereContainsRealData()
    {
        $odataClient = $this->createODataClient();

        // Test orWhereContains - find Air France OR names containing "China"
        $airlines = $odataClient->from('Airlines')
            ->where('AirlineCode', 'AF')
            ->orWhereContains('Name', 'China')
            ->get();

        $this->assertTrue(is_array($airlines->toArray()));
        $this->assertGreaterThanOrEqual(2, $airlines->count()); // At least Air France and China Eastern
        
        $airlineCodes = $airlines->pluck('AirlineCode')->toArray();
        $this->assertContains('AF', $airlineCodes); // Air France
        $this->assertContains('MU', $airlineCodes); // China Eastern Airlines
    }

    public function testODataClientWhereNotContainsRealData()
    {
        $odataClient = $this->createODataClient();

        // Test whereNotContains - should find airlines WITHOUT "Airline" in their name
        $airlines = $odataClient->from('Airlines')
            ->whereNotContains('Name', 'Airline')
            ->get();

        $this->assertTrue(is_array($airlines->toArray()));
        $this->assertGreaterThan(0, $airlines->count());
        
        // Verify none of the results contain "Airline" in the name
        foreach ($airlines as $airline) {
            $this->assertStringNotContainsString('Airline', $airline->Name);
        }

        // Should find Air France, Alitalia, Air Canada, and other non-Airline named carriers
        $airlineCodes = $airlines->pluck('AirlineCode')->toArray();
        $this->assertContains('AF', $airlineCodes); // Air France
        $this->assertContains('AZ', $airlineCodes); // Alitalia
        $this->assertContains('AC', $airlineCodes); // Air Canada
        $this->assertNotContains('AA', $airlineCodes); // American Airlines should NOT be included
        $this->assertNotContains('FM', $airlineCodes); // Shanghai Airline should NOT be included
    }

    public function testODataClientOrWhereNotContainsRealData()
    {
        $odataClient = $this->createODataClient();

        // Test orWhereNotContains - find Turkish Airlines OR names not containing "Air"
        $airlines = $odataClient->from('Airlines')
            ->where('AirlineCode', 'TK')
            ->orWhereNotContains('Name', 'Air')
            ->get();

        $this->assertTrue(is_array($airlines->toArray()));
        $this->assertGreaterThan(0, $airlines->count());
        
        $airlineCodes = $airlines->pluck('AirlineCode')->toArray();
        $this->assertContains('TK', $airlineCodes); // Turkish Airlines (matched by first condition)
        $this->assertContains('AZ', $airlineCodes); // Alitalia (no "Air" in name)
        // Note: Results may vary based on the current dataset
    }

    public function testODataClientCombinedContainsNotContainsRealData()
    {
        $odataClient = $this->createODataClient();

        // Test combined contains/notContains - names with "Air" but not "Airline"
        $airlines = $odataClient->from('Airlines')
            ->whereContains('Name', 'Air')
            ->whereNotContains('Name', 'Airline')
            ->get();

        $this->assertTrue(is_array($airlines->toArray()));
        $this->assertGreaterThan(0, $airlines->count());
        
        // Should find Air France, Air Canada, Alitalia
        $airlineNames = $airlines->pluck('Name')->toArray();
        foreach ($airlineNames as $name) {
            $this->assertStringContainsString('Air', $name);
            $this->assertStringNotContainsString('Airline', $name);
        }

        $airlineCodes = $airlines->pluck('AirlineCode')->toArray();
        $this->assertContains('AF', $airlineCodes); // Air France
        $this->assertContains('AC', $airlineCodes); // Air Canada  
        $this->assertNotContains('AA', $airlineCodes); // American Airlines (contains both)
        $this->assertNotContains('OS', $airlineCodes); // Austrian Airlines (contains both)
        $this->assertNotContains('FM', $airlineCodes); // Shanghai Airline (contains both)
    }

    public function testODataClientPutMethodExists()
    {
        $odataClient = $this->createODataClient();
        $this->assertTrue(method_exists($odataClient, 'put'));
    }

    // Nested Properties Integration Tests

    public function testNestedPropertiesWithRealTripPinData()
    {
        $odataClient = $this->createODataClient();
        
        // Get a specific person that we know exists in TripPin service
        $person = $odataClient->from('People')->find('russellwhyte');
        
        $this->assertNotNull($person);
        
        // Test basic property access
        $this->assertEquals('russellwhyte', $person->UserName);
        $this->assertEquals('Russell', $person->FirstName);
        $this->assertEquals('Whyte', $person->LastName);
        
        // Test nested AddressInfo property access (complex type collection)
        if (property_exists($person, 'AddressInfo') && !empty($person->AddressInfo)) {
            // AddressInfo should be an array of address objects
            $this->assertIsArray($person->AddressInfo);
            $this->assertGreaterThan(0, count($person->AddressInfo));
            
            // Test accessing the first address as an array (backward compatibility)
            $firstAddress = $person->AddressInfo[0];
            $this->assertIsArray($firstAddress);
            
            if (isset($firstAddress['Address'])) {
                $this->assertIsString($firstAddress['Address']);
            }
            
            if (isset($firstAddress['City'])) {
                $this->assertIsArray($firstAddress['City']);
                if (isset($firstAddress['City']['Name'])) {
                    $this->assertIsString($firstAddress['City']['Name']);
                }
            }
        }
    }

    public function testNestedPropertyAccessWithDotNotation()
    {
        $odataClient = $this->createODataClient();
        
        $person = $odataClient->from('People')->find('russellwhyte');
        $this->assertNotNull($person);
        
        // Test hasProperty method with simple properties
        $this->assertTrue($person->hasProperty('UserName'));
        $this->assertTrue($person->hasProperty('FirstName'));
        $this->assertFalse($person->hasProperty('NonExistentProperty'));
        
        // Test hasProperty with nested properties if they exist
        if (property_exists($person, 'AddressInfo') && !empty($person->AddressInfo)) {
            $this->assertTrue($person->hasProperty('AddressInfo'));
            
            // Check if we can access nested properties using dot notation
            $addressInfo = $person->getProperty('AddressInfo');
            $this->assertNotNull($addressInfo);
            $this->assertIsArray($addressInfo);
        }
        
        // Test accessing non-existent nested properties safely
        $this->assertNull($person->getProperty('NonExistent.Property'));
        $this->assertNull($person->getProperty('AddressInfo.NonExistent'));
    }

    public function testNestedPropertiesWithExpandedData()
    {
        $odataClient = $this->createODataClient();
        
        // Test with expanded navigation properties
        $person = $odataClient->from('People')
            ->expand('Friends')
            ->find('russellwhyte');
        
        $this->assertNotNull($person);
        
        // Test basic properties still work
        $this->assertEquals('russellwhyte', $person->UserName);
        
        // Test Friends navigation property if it exists
        if (property_exists($person, 'Friends')) {
            $friends = $person->Friends;
            
            if (!empty($friends)) {
                $this->assertIsArray($friends);
                
                // Test accessing friend properties
                $firstFriend = $friends[0];
                $this->assertIsArray($firstFriend);
                
                if (isset($firstFriend['UserName'])) {
                    $this->assertIsString($firstFriend['UserName']);
                }
                
                if (isset($firstFriend['FirstName'])) {
                    $this->assertIsString($firstFriend['FirstName']);
                }
            }
        }
    }

    public function testNestedPropertiesObjectStyleAccess()
    {
        $odataClient = $this->createODataClient();
        
        $person = $odataClient->from('People')->find('russellwhyte');
        $this->assertNotNull($person);
        
        // Test that complex properties are converted to Entity objects for object-style access
        if (property_exists($person, 'AddressInfo') && !empty($person->AddressInfo)) {
            $addressInfo = $person->AddressInfo;
            $this->assertIsArray($addressInfo);
            
            // For complex nested objects, test if they can be accessed as properties
            $firstAddress = $addressInfo[0];
            if (is_array($firstAddress) && !empty($firstAddress)) {
                // Create an Entity from the address data to test object-style access
                $addressEntity = new Entity($firstAddress);
                
                // Test isset functionality on nested objects
                if (isset($firstAddress['Address'])) {
                    $this->assertTrue(isset($addressEntity->Address));
                }
                
                if (isset($firstAddress['City'])) {
                    $this->assertTrue(isset($addressEntity->City));
                    
                    // Test deeper nesting if City is an object
                    if (is_array($firstAddress['City'])) {
                        $cityEntity = $addressEntity->City;
                        $this->assertInstanceOf(Entity::class, $cityEntity);
                        
                        if (isset($firstAddress['City']['Name'])) {
                            $this->assertTrue(isset($cityEntity->Name));
                            $this->assertIsString($cityEntity->Name);
                        }
                    }
                }
            }
        }
    }

    public function testNestedPropertiesBackwardCompatibility()
    {
        $odataClient = $this->createODataClient();
        
        $person = $odataClient->from('People')->find('russellwhyte');
        $this->assertNotNull($person);
        
        // Test that array access still works (backward compatibility)
        $this->assertEquals('russellwhyte', $person['UserName']);
        $this->assertEquals('Russell', $person['FirstName']);
        $this->assertEquals('Whyte', $person['LastName']);
        
        // Test nested array access if AddressInfo exists
        if (property_exists($person, 'AddressInfo') && !empty($person->AddressInfo)) {
            $this->assertIsArray($person['AddressInfo']);
            $this->assertGreaterThan(0, count($person['AddressInfo']));
            
            $firstAddress = $person['AddressInfo'][0];
            $this->assertIsArray($firstAddress);
            
            // Test nested array access
            if (isset($firstAddress['City']) && is_array($firstAddress['City'])) {
                $this->assertIsArray($person['AddressInfo'][0]['City']);
                
                if (isset($firstAddress['City']['Name'])) {
                    $this->assertIsString($person['AddressInfo'][0]['City']['Name']);
                }
            }
        }
    }

    public function testNestedPropertiesWithPeopleCollection()
    {
        $odataClient = $this->createODataClient();
        
        // Get multiple people and test nested property access on collection
        $people = $odataClient->from('People')->take(3)->get();
        
        $this->assertGreaterThan(0, $people->count());
        
        foreach ($people as $person) {
            $this->assertInstanceOf(Entity::class, $person);
            
            // Test basic properties exist
            $this->assertTrue($person->hasProperty('UserName'));
            $this->assertTrue($person->hasProperty('FirstName'));
            $this->assertTrue($person->hasProperty('LastName'));
            
            // Test that properties can be accessed both ways
            $this->assertEquals($person->UserName, $person['UserName']);
            $this->assertEquals($person->FirstName, $person['FirstName']);
            $this->assertEquals($person->LastName, $person['LastName']);
            
            // Test nested properties if they exist
            if ($person->hasProperty('AddressInfo')) {
                $addressInfo = $person->getProperty('AddressInfo');
                if (!empty($addressInfo)) {
                    $this->assertIsArray($addressInfo);
                    
                    // Test accessing first address
                    if (count($addressInfo) > 0) {
                        $firstAddress = $addressInfo[0];
                        $this->assertIsArray($firstAddress);
                    }
                }
            }
        }
    }
}
