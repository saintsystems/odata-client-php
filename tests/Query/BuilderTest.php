<?php

namespace SaintSystems\OData\Query\Tests;

use PHPUnit\Framework\TestCase;

use Illuminate\Support\Collection;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\QueryOptions;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Exception\ODataQueryException;

class BuilderTest extends TestCase
{
    protected $baseUrl;
    protected $client;

    public function setUp(): void
    {
        $this->baseUrl = 'https://services.odata.org/V4/TripPinService';
        $httpProvider = new GuzzleHttpProvider();
        $this->client = new ODataClient($this->baseUrl, null, $httpProvider);
    }

    public function getBuilder()
    {
        return new Builder(
            $this->client, $this->client->getQueryGrammar(), $this->client->getPostProcessor()
        );
    }

    public function testConstructor()
    {
        $builder = $this->getBuilder();

        $this->assertNotNull($builder);
    }

    public function testEntitySet()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet);

        $expected = $entitySet;
        $actual = $builder->entitySet;

        $this->assertEquals($expected, $actual);

        $request = $builder->toRequest();
        $this->assertEquals($expected, $request);
    }

    public function testNoEntitySetFind()
    {
        $this->expectException(ODataQueryException::class);

        $builder = $this->getBuilder();
        $builder->find('russellwhyte');
    }

    public function testEntitySetFindStringKey()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet);

        $builder->whereKey('russellwhyte');

        $expected = $entitySet.'(\'russellwhyte\')';
        $actual = $builder->toRequest();

        $this->assertEquals($expected, $actual);
    }

    public function testEntitySetFindNumericKey()
    {
        $builder = $this->getBuilder();

        $entitySet = 'EntitySet';

        $builder->from($entitySet);

        $builder->whereKey(12345);

        $expected = $entitySet.'(12345)';
        $actual = $builder->toRequest();

        $this->assertEquals($expected, $actual);
    }

    public function testEntitySetWithSelect()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->select('FirstName','LastName')->from($entitySet);

        $expected = $entitySet.'?$select=FirstName,LastName';

        $request = $builder->toRequest();

        $this->assertEquals($expected, $request);
    }

    public function testEntitySetCount()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $expected = 20;

        $actual = $builder->from($entitySet)->count();

        $this->assertTrue(is_numeric($actual));
        $this->assertTrue($actual > 0);
        $this->assertEquals($expected, $actual);
    }

    // public function testEntitySetCountWithWhere()
    // {
    //     $builder = $this->getBuilder();

    //     $entitySet = 'People';

    //     $expected = 1;

    //     $actual = $builder->from($entitySet)->where('FirstName','Russell')->get(QueryOptions::INCLUDE_REF | QueryOptions::INCLUDE_COUNT);

    //     $this->assertTrue(is_numeric($actual));
    //     $this->assertTrue($actual > 0);
    //     $this->assertEquals($expected, $actual);
    // }

    public function testEntitySetGet()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $people = $builder->from($entitySet)->get();

        // dd($people);
        $this->assertTrue(is_array($people->toArray()));
        //$this->assertInstanceOf(Collection::class, $people);
        //$this->assertEquals($expected, $request);
    }

    public function testEntitySetGetWhere()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $people = $builder->from($entitySet)->where('FirstName','Russell')->get();

        // dd($people);
        $this->assertTrue(is_array($people->toArray()));
        $this->assertTrue($people->count() == 1);
        //$this->assertInstanceOf(Collection::class, $people);
        //$this->assertEquals($expected, $request);
    }

    public function testEntitySetGetWhereOrWhere()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $people = $builder->from($entitySet)->where('FirstName','Russell')->orWhere('LastName','Ketchum')->get();

        //dd($people);
        $this->assertTrue(is_array($people->toArray()));
        $this->assertTrue($people->count() == 2);
        //$this->assertInstanceOf(Collection::class, $people);
        //$this->assertEquals($expected, $request);
    }

    public function testEntitySetGetWhereNested()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $people = $builder->from($entitySet)->where('FirstName','Russell')->orWhere(function($query) {
            $query->where('LastName','Ketchum')
                  ->where('FirstName','Scott');
        })->get();

        //dd($people);
        $this->assertTrue(is_array($people->toArray()));
        $this->assertTrue($people->count() == 2);
        //$this->assertInstanceOf(Collection::class, $people);
        //$this->assertEquals($expected, $request);
    }

    public function testEntityKeyString()
    {
        $builder = $this->getBuilder();

        $entityId = 'russellwhyte';

        $builder->whereKey($entityId);

        $expected = $entityId;
        $actual = $builder->entityKey;

        $this->assertEquals($expected, $actual);

        $expectedUri = "('$entityId')";
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityKeyNumeric()
    {
        $builder = $this->getBuilder();

        $entityId = 1;

        $builder->whereKey($entityId);

        $expected = $entityId;
        $actual = $builder->entityKey;

        $this->assertEquals($expected, $actual);

        $expectedUri = "($entityId)";
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityKeyUuid()
    {
        $builder = $this->getBuilder();

        $entityId = 'c78ae94b-0983-e511-80e5-3863bb35ddb8';

        $builder->whereKey($entityId);

        $expected = $entityId;
        $actual = $builder->entityKey;

        $this->assertEquals($expected, $actual);

        $expectedUri = "($entityId)";
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityKeyUuidNegative()
    {
        $builder = $this->getBuilder();

        $entityId = 'k78ae94b-0983-t511-80e5-3863bb35ddb8';

        $builder->whereKey($entityId);

        $expected = $entityId;
        $actual = $builder->entityKey;

        $this->assertEquals($expected, $actual);

        $expectedUri = "('$entityId')";
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityKeyComposite()
    {
        $builder = $this->getBuilder();

        $compositeKey = [
            'Property1' => 'Value1',
            'Property2' => 'Value2',
        ];

        $builder->whereKey($compositeKey);

        $expectedUri = "(Property1='Value1',Property2='Value2')";
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testTake()
    {
        $builder = $this->getBuilder();

        $take = 1;

        $builder->take($take);

        $expected = $take;
        $actual = $builder->take;

        $this->assertEquals($expected, $actual);
    }

    public function testSkip()
    {
        $builder = $this->getBuilder();

        $skip = 5;

        $builder->skip($skip);

        $expected = $skip;
        $actual = $builder->skip;

        $this->assertEquals($expected, $actual);
    }

    public function testOrderColumnOnly()
    {
        $builder = $this->getBuilder();

        $builder->order('Name'); // default asc

        $expectedUri = '$orderby=Name asc';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testOrderWithDirection()
    {
        $builder = $this->getBuilder();

        $builder->order('Name', 'desc');

        $expectedUri = '$orderby=Name desc';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testOrderWithShortArray()
    {
        $builder = $this->getBuilder();

        $builder->order(['Name', 'desc']);

        $expectedUri = '$orderby=Name desc';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testOrderWithMultipleShortArray()
    {
        $builder = $this->getBuilder();

        $builder->order(['Id', 'asc'], ['Name', 'desc']);

        $expectedUri = '$orderby=Id asc,Name desc';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testOrderWithMultipleNestedShortArray()
    {
        $builder = $this->getBuilder();

        $builder->order(array(['Id', 'asc'], ['Name', 'desc']));

        $expectedUri = '$orderby=Id asc,Name desc';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testOrderWithArray()
    {
        $builder = $this->getBuilder();

        $builder->order(['column' => 'Name', 'direction' => 'desc']);

        $expectedUri = '$orderby=Name desc';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testOrderWithMultipleArray()
    {
        $builder = $this->getBuilder();

        $builder->order(['column' => 'Id', 'direction' => 'asc'], ['column' => 'Name', 'direction' => 'desc']);

        $expectedUri = '$orderby=Id asc,Name desc';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testOrderWithMultipleNestedArray()
    {
        $builder = $this->getBuilder();

        $builder->order(array(['column' => 'Id', 'direction' => 'asc'], ['column' => 'Name', 'direction' => 'desc']));

        $expectedUri = '$orderby=Id asc,Name desc';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testMultipleChainedQueryParams()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
                ->select('Name,Gender')
                ->where('Gender', '=', 'Female')
                ->order('Name', 'desc')
                ->take(5);

        $expectedUri = 'People?$select=Name,Gender&$filter=Gender eq \'Female\'&$orderby=Name desc&$top=5';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereEnum()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';
        $whereEnum = 'Microsoft.OData.Service.Sample.TrippinInMemory.Models.PersonGender\'Female\'';

        $builder->from($entitySet)
                ->where('Gender', '=', $whereEnum);

        $expectedUri = 'People?$filter=Gender eq Microsoft.OData.Service.Sample.TrippinInMemory.Models.PersonGender\'Female\'';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithSingleExpand()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';
        $expand = 'PersonGender';

        $builder->from($entitySet)
                ->expand($expand);

        $expectedUri = 'People?$expand=PersonGender';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithMultipleExpand()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';
        $expands = ['PersonGender', 'PersonOccupation'];

        $builder->from($entitySet)
                ->expand($expands);

        $expectedUri = 'People?$expand=PersonGender,PersonOccupation';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereColumn()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
                ->whereColumn('FirstName', 'LastName');

        $expectedUri = 'People?$filter=FirstName eq LastName';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithOrWhereColumnO()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
                ->where('FirstName', '=', 'Russell')
                ->orWhereColumn('FirstName', 'LastName');

        $expectedUri = 'People?$filter=FirstName eq \'Russell\' or FirstName eq LastName';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereNull()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
                ->whereNull('FirstName');

        $expectedUri = 'People?$filter=FirstName eq null';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereNotNull()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
                ->whereNotNull('FirstName');

        $expectedUri = 'People?$filter=FirstName ne null';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereIn()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
                ->whereIn('FirstName', ['John', 'Jane']);

        $expectedUri = 'People?$filter=FirstName in (\'John\',\'Jane\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereNotIn()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
                ->whereNotIn('FirstName', ['John', 'Jane']);

        $expectedUri = 'People?$filter=not(FirstName in (\'John\',\'Jane\'))';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWhereString()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
                ->where('FirstName', 'Russell');

        $expectedUri = 'People?$filter=FirstName eq \'Russell\'';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWhereNumeric()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Photos';

        $builder->from($entitySet)
                ->where('Id', 1);

        $expectedUri = 'Photos?$filter=Id eq 1';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityMultipleWheres()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
            ->where('FirstName', 'Russell')
            ->where('LastName', 'Whyte');

        $expectedUri = 'People?$filter=FirstName eq \'Russell\' and LastName eq \'Whyte\'';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityMultipleWheresArray()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
            ->where([
                ['FirstName', 'Russell'],
                ['LastName', 'Whyte'],
            ]);

        $expectedUri = 'People?$filter=(FirstName eq \'Russell\' and LastName eq \'Whyte\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityMultipleWheresArrayWithSelect()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
            ->select('Name')
            ->where([
                ['FirstName', 'Russell'],
                ['LastName', 'Whyte'],
            ]);

        $expectedUri = 'People?$select=Name&$filter=(FirstName eq \'Russell\' and LastName eq \'Whyte\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityMultipleWheresNested()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
            ->where(function($query) {
                $query->where('FirstName','Russell');
                $query->where('LastName','Whyte');
            });

        $expectedUri = 'People?$filter=(FirstName eq \'Russell\' and LastName eq \'Whyte\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityMultipleWheresNestedWithSelect()
    {
        $builder = $this->getBuilder();

        $entitySet = 'People';

        $builder->from($entitySet)
            ->select('Name')
            ->where(function($query) {
                $query->where('FirstName','Russell');
                $query->where('LastName','Whyte');
            });

        $expectedUri = 'People?$select=Name&$filter=(FirstName eq \'Russell\' and LastName eq \'Whyte\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereContains()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Airlines';

        $builder->from($entitySet)
                ->whereContains('Name', 'Airline');

        $expectedUri = 'Airlines?$filter=contains(Name,\'Airline\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithOrWhereContains()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Airlines';

        $builder->from($entitySet)
                ->where('AirlineCode', 'AA')
                ->orWhereContains('Name', 'Airline');

        $expectedUri = 'Airlines?$filter=AirlineCode eq \'AA\' or contains(Name,\'Airline\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereNotContains()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Airlines';

        $builder->from($entitySet)
                ->whereNotContains('Name', 'Airline');

        $expectedUri = 'Airlines?$filter=indexof(Name,\'Airline\') eq -1';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithOrWhereNotContains()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Airlines';

        $builder->from($entitySet)
                ->where('AirlineCode', 'AA')
                ->orWhereNotContains('Name', 'Airline');

        $expectedUri = 'Airlines?$filter=AirlineCode eq \'AA\' or indexof(Name,\'Airline\') eq -1';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithMultipleContains()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Airlines';

        $builder->from($entitySet)
                ->whereContains('Name', 'Air')
                ->whereNotContains('Name', 'Airline');

        $expectedUri = 'Airlines?$filter=contains(Name,\'Air\') and indexof(Name,\'Airline\') eq -1';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithDateTimeValue()
    {
        $builder = $this->getBuilder();

        $entitySet = 'ProductSearch';

        $builder->from($entitySet)
                ->where('UpdatedAt', '>=', '2000-02-05T08:48:36+08:00');

        $expectedUri = 'ProductSearch?$filter=UpdatedAt ge 2000-02-05T08:48:36+08:00';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithUrlEncodedDateTimeValue()
    {
        $builder = $this->getBuilder();

        $entitySet = 'ProductSearch';
        
        // Simulate URL-encoded datetime as user would provide
        $filterEarliest = urlencode('2000-02-05T08:48:36+08:00');
        $filterLatest = urlencode('2023-03-05T08:48:36+08:00');

        $builder->from($entitySet)
                ->where('UpdatedAt', '>=', $filterEarliest)
                ->where('UpdatedAt', '<=', $filterLatest);

        // Expected: URL-encoded datetime values should NOT be wrapped in quotes
        $expectedUri = 'ProductSearch?$filter=UpdatedAt ge 2000-02-05T08%3A48%3A36%2B08%3A00 and UpdatedAt le 2023-03-05T08%3A48%3A36%2B08%3A00';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithVariousDateTimeFormats()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Events';

        // Test different datetime formats
        $cases = [
            // ISO 8601 basic
            ['2023-12-25T10:30:00', '2023-12-25T10:30:00'],
            // ISO 8601 with timezone
            ['2023-12-25T10:30:00+05:00', '2023-12-25T10:30:00+05:00'],
            // URL encoded with timezone  
            [urlencode('2023-12-25T10:30:00+05:00'), '2023-12-25T10%3A30%3A00%2B05%3A00'],
            // URL encoded UTC
            [urlencode('2023-12-25T10:30:00Z'), '2023-12-25T10%3A30%3A00Z'],
        ];

        foreach ($cases as [$input, $expected]) {
            $builder = $this->getBuilder();
            $builder->from($entitySet)->where('EventDate', '>=', $input);
            
            $expectedUri = "Events?\$filter=EventDate ge $expected";
            $actualUri = $builder->toRequest();
            
            $this->assertEquals($expectedUri, $actualUri, "Failed for input: $input");
        }
    }

    public function testEntityWithWhereAny()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Customers';

        $builder->from($entitySet)
                ->whereAny('Orders', function($query) {
                    $query->where('Status', 'Completed');
                });

        $expectedUri = 'Customers?$filter=Orders/any(o: o/Status eq \'Completed\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithWhereAll()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Customers';

        $builder->from($entitySet)
                ->whereAll('Orders', function($query) {
                    $query->where('Amount', '>', 100);
                });

        $expectedUri = 'Customers?$filter=Orders/all(o: o/Amount gt 100)';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithOrWhereAny()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Customers';

        $builder->from($entitySet)
                ->where('Status', 'Active')
                ->orWhereAny('Orders', function($query) {
                    $query->where('Status', 'Pending');
                });

        $expectedUri = 'Customers?$filter=Status eq \'Active\' or Orders/any(o: o/Status eq \'Pending\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithOrWhereAll()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Customers';

        $builder->from($entitySet)
                ->where('Status', 'Active')
                ->orWhereAll('Orders', function($query) {
                    $query->where('Amount', '<', 50);
                });

        $expectedUri = 'Customers?$filter=Status eq \'Active\' or Orders/all(o: o/Amount lt 50)';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithComplexLambdaCondition()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Customers';

        $builder->from($entitySet)
                ->whereAny('Orders', function($query) {
                    $query->where('Status', 'Completed')
                          ->where('Amount', '>', 100);
                });

        $expectedUri = 'Customers?$filter=Orders/any(o: o/Status eq \'Completed\' and o/Amount gt 100)';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithMultipleLambdaOperators()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Customers';

        $builder->from($entitySet)
                ->whereAny('Orders', function($query) {
                    $query->where('Status', 'Completed');
                })
                ->whereAll('Invoices', function($query) {
                    $query->where('Paid', true);
                });

        $expectedUri = 'Customers?$filter=Orders/any(o: o/Status eq \'Completed\') and Invoices/all(i: i/Paid eq true)';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithLambdaOperatorAndSelect()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Customers';

        $builder->from($entitySet)
                ->select('Name', 'Email')
                ->whereAny('Orders', function($query) {
                    $query->where('Status', 'Pending');
                });

        $expectedUri = 'Customers?$select=Name,Email&$filter=Orders/any(o: o/Status eq \'Pending\')';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithNestedLambdaCondition()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Customers';

        $builder->from($entitySet)
                ->whereAny('Orders', function($query) {
                    $query->where(function($nested) {
                        $nested->where('Status', 'Completed')
                               ->orWhere('Status', 'Shipped');
                    })->where('Amount', '>', 50);
                });

        $expectedUri = 'Customers?$filter=Orders/any(o: (o/Status eq \'Completed\' or o/Status eq \'Shipped\') and o/Amount gt 50)';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityWithDifferentNavigationPropertyNames()
    {
        $builder = $this->getBuilder();

        $entitySet = 'Products';

        $builder->from($entitySet)
                ->whereAny('Reviews', function($query) {
                    $query->where('Rating', '>=', 4);
                })
                ->whereAll('Suppliers', function($query) {
                    $query->where('Verified', true);
                });

        $expectedUri = 'Products?$filter=Reviews/any(r: r/Rating ge 4) and Suppliers/all(s: s/Verified eq true)';
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

}
