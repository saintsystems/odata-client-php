<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\Entity;
use SaintSystems\OData\ODataClient;
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

    public function testODataClientConstructor()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $baseUrl = $odataClient->getBaseUrl();
        $this->assertEquals('https://services.odata.org/V4/TripPinService/', $baseUrl);
    }

    public function testODataClientEntitySetQuery()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')->get();
        $this->assertTrue(is_array($people->toArray()));
    }

    public function testODataClientEntitySetQueryWithSelect()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $people = $odataClient->select('FirstName','LastName')->from('People')->get();
        $this->assertTrue(is_array($people->toArray()));
    }

    public function testODataClientFromQueryWithWhere()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')->where('FirstName','Russell')->get();
        $this->assertTrue(is_array($people->toArray()));
        $this->assertEquals(1, $people->count());
    }

    public function testODataClientFromQueryWithWhereOrWhere()
    {
        $odataClient = new ODataClient($this->baseUrl);
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
        $odataClient = new ODataClient($this->baseUrl);
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
        $odataClient = new ODataClient($this->baseUrl);
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
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $person = $odataClient->from('People')->find('russellwhyte');
        $this->assertEquals('russellwhyte', $person->UserName);
    }

    public function testODataClientSkipToken()
    {
        $pageSize = 8;
        $odataClient = new ODataClient($this->baseUrl, function($request) use($pageSize) {
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
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $this->assertInstanceOf(LazyCollection::class, $data);
    }

    public function testODataClientCursorCountShouldEqualTotalEntitySetCount()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $expectedCount = 20;

        $this->assertEquals($expectedCount, $data->count());
    }

    public function testODataClientCursorToArrayCountShouldEqualPageSize()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $this->assertEquals($pageSize, count($data->toArray()));
    }

    public function testODataClientCursorFirstShouldReturnEntityRussellWhyte()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $first = $data->first();
        $this->assertInstanceOf(Entity::class, $first);
        $this->assertEquals('russellwhyte', $first->UserName);
    }

    public function testODataClientCursorLastShouldReturnEntityKristaKemp()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $last = $data->last();
        $this->assertInstanceOf(Entity::class, $last);
        $this->assertEquals('kristakemp', $last->UserName);
    }

    public function testODataClientCursorSkip1FirstShouldReturnEntityScottKetchum()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $second = $data->skip(1)->first();
        $this->assertInstanceOf(Entity::class, $second);
        $this->assertEquals('scottketchum', $second->UserName);
    }

    public function testODataClientCursorSkip4FirstShouldReturnEntityWillieAshmore()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $fifth = $data->skip(4)->first();
        $this->assertInstanceOf(Entity::class, $fifth);
        $this->assertEquals('willieashmore', $fifth->UserName);
    }

    public function testODataClientCursorSkip7FirstShouldReturnEntityKeithPinckney()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $eighth = $data->skip(7)->first();
        $this->assertInstanceOf(Entity::class, $eighth);
        $this->assertEquals('keithpinckney', $eighth->UserName);
    }

    public function testODataClientCursorSkip8FirstShouldReturnEntityMarshallGaray()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $ninth = $data->skip(8)->first();
        $this->assertInstanceOf(Entity::class, $ninth);
        $this->assertEquals('marshallgaray', $ninth->UserName);
    }

    public function testODataClientCursorSkip16FirstShouldReturnEntitySandyOsbord()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $seventeenth = $data->skip(16)->first();
        $this->assertInstanceOf(Entity::class, $seventeenth);
        $this->assertEquals('sandyosborn', $seventeenth->UserName);
    }

    public function testODataClientCursorSkip16LastPageShouldBe4Records()
    {
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $lastPage = $data->skip(16);
        $lastPageSize = 4;
        $this->assertEquals($lastPageSize, count($lastPage->toArray()));
    }

    public function testODataClientCursorIteratingShouldReturnAll20Entities()
    {
        $odataClient = new ODataClient($this->baseUrl);

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
        $odataClient = new ODataClient($this->baseUrl);

        $pageSize = 20;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $this->assertEquals($pageSize, count($data->toArray()));
    }
}
