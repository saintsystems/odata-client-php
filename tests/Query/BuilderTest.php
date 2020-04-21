<?php

namespace SaintSystems\OData\Query\Tests;

use PHPUnit\Framework\TestCase;

use Illuminate\Support\Collection;
use SaintSystems\OData\ODataClient;
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
        $this->client = new ODataClient($this->baseUrl);
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
        $actual = $this->readAttribute($builder, 'entitySet');

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

        //$expected = 55;

        $actual = $builder->from($entitySet)->count();

        $this->assertTrue(is_numeric($actual));
        $this->assertTrue($actual > 0);
        //$this->assertEquals($expected, $actual);
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
        $actual = $this->readAttribute($builder, 'entityKey');

        $this->assertEquals($expected, $actual);

        $expectedUri = "('$entityId')";
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testEntityKeyNumeric()
    {
        $builder = $this->getBuilder();

        $entityId = '1';

        $builder->whereKey($entityId);

        $expected = $entityId;
        $actual = $this->readAttribute($builder, 'entityKey');

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
        $actual = $this->readAttribute($builder, 'entityKey');

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
        $actual = $this->readAttribute($builder, 'entityKey');

        $this->assertEquals($expected, $actual);

        $expectedUri = "('$entityId')";
        $actualUri = $builder->toRequest();

        $this->assertEquals($expectedUri, $actualUri);
    }

    public function testTake()
    {
        $builder = $this->getBuilder();

        $take = 1;

        $builder->take($take);

        $expected = $take;
        $actual = $this->readAttribute($builder, 'take');

        $this->assertEquals($expected, $actual);
    }

    public function testSkip()
    {
        $builder = $this->getBuilder();

        $skip = 5;

        $builder->skip($skip);

        $expected = $skip;
        $actual = $this->readAttribute($builder, 'skip');

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

}
