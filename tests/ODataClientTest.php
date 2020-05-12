<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;

use SaintSystems\OData\ODataClient;

class ODataClientTest extends TestCase
{
    private $baseUrl;

    public function setUp(): void
    {
        $this->baseUrl = 'http://services.odata.org/V4/TripPinService';
    }

    public function testODataClientConstructor()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $baseUrl = $this->readAttribute($odataClient, 'baseUrl');
        $this->assertEquals('http://services.odata.org/V4/TripPinService/', $baseUrl);
    }

    public function testODataClientEntitySetQuery()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')->get();
        //dd($people);
        $this->assertTrue(is_array($people->toArray()));
    }

    public function testODataClientEntitySetQueryWithSelect()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $people = $odataClient->select('FirstName','LastName')->from('People')->get();
        //dd($people);
        $this->assertTrue(is_array($people->toArray()));
    }

    public function testODataClientFromQueryWithWhere()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $people = $odataClient->from('People')->where('FirstName','Russell')->get();
        // dd($people);
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

    public function testODataClientFind()
    {
        $odataClient = new ODataClient($this->baseUrl);
        $this->assertNotNull($odataClient);
        $person = $odataClient->from('People')->find('russellwhyte');
        //dd($person);
        $this->assertEquals('Russell', $person->FirstName);
    }
}
