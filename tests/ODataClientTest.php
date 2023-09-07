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
        $this->assertTrue($people->count() == 1);
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
        $this->assertTrue($people->count() == 2);
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
        $this->assertTrue($people->count() == 2);
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
        $this->assertTrue($people->count() == 2);
    }

    public function testODataClientFind()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $person = $odataClient->from('People')->find('russellwhyte');
        $this->assertEquals('Russell', $person->FirstName);
    }

    public function testODataClientSkipToken()
    {
        $odataClient = new ODataClient($this->baseUrl, function($request) {
            $request->headers[RequestHeader::PREFER] = Constants::ODATA_MAX_PAGE_SIZE . '=' . 8;
        });
        $this->assertNotNull($odataClient);
        $odataClient->setEntityReturnType(false);
        $page1response = $odataClient->from('People')->get()->first();
        $page1results = collect($page1response->getResponseAsObject(Entity::class));
        $this->assertEquals($page1results->count(), 8);

        $page1skiptoken = $page1response->getSkipToken();
        if ($page1skiptoken) {
            $page2response = $odataClient->from('People')->skiptoken($page1skiptoken)->get()->first();
            $page2results = collect($page2response->getResponseAsObject(Entity::class));
            $page2skiptoken = $page2response->getSkipToken();
            $this->assertEquals($page2results->count(), 8);
        }

        if ($page2skiptoken) {
            $page3response = $odataClient->from('People')->skiptoken($page2skiptoken)->get()->first();
            $page3results = collect($page3response->getResponseAsObject(Entity::class));
            $page3skiptoken = $page3response->getSkipToken();
            $this->assertEquals($page3results->count(), 4);
            $this->assertNull($page3skiptoken);
        }
    }

    public function testODataClientLazyCollection()
    {
        $odataClient = new ODataClient($this->baseUrl, function($request) {
            //$request->headers[RequestHeader::PREFER] = Constants::ODATA_MAX_PAGE_SIZE . '=' . 8;
        });

        $pageSize = 8;

        $data = $odataClient->from('People')->pageSize($pageSize)->cursor();

        $expectedCount = 20;

        $this->assertInstanceOf(LazyCollection::class, $data);
        $this->assertEquals($data->count(), $expectedCount);

        $this->assertInstanceOf(LazyCollection::class, $data);
        $this->assertEquals(count($data->toArray()), $pageSize);

        $first = $data->first();
        $this->assertInstanceOf(Entity::class, $first);
        $this->assertEquals($first->FirstName, 'Russell');

        $last = $data->last();
        $this->assertInstanceOf(Entity::class, $last);
        $this->assertEquals($last->UserName, 'kristakemp');

        $second = $data->skip(1)->first();
        $this->assertInstanceOf(Entity::class, $second);
        $this->assertEquals($second->FirstName, 'Scott');

        $fifth = $data->skip(4)->first();
        $this->assertInstanceOf(Entity::class, $fifth);
        $this->assertEquals($fifth->UserName, 'willieashmore');

        $eighth = $data->skip(7)->first();
        $this->assertInstanceOf(Entity::class, $eighth);
        $this->assertEquals($eighth->UserName, 'keithpinckney');

        $ninth = $data->skip(8)->first();
        $this->assertInstanceOf(Entity::class, $ninth);
        $this->assertEquals($ninth->UserName, 'marshallgaray');

        $seventeenth = $data->skip(16)->first();
        $this->assertInstanceOf(Entity::class, $seventeenth);
        $this->assertEquals($seventeenth->UserName, 'sandyosborn');

        $lastPage = $data->skip(16);
        $this->assertEquals(count($lastPage->toArray()), 4);

        $data->each(function ($person) {
            $this->assertInstanceOf(Entity::class, $person);
        });
    }
}
