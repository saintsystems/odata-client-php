<?php

namespace SaintSystems\OData\Query\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataClient;
use SaintSystems\OData\GuzzleHttpProvider;
use SaintSystems\OData\Query\Builder;
use SaintSystems\OData\Query\Grammar;
use SaintSystems\OData\Query\Processor;
use SaintSystems\OData\IHttpProvider;
use SaintSystems\OData\IODataRequest;
use SaintSystems\OData\IODataResponse;
use SaintSystems\OData\ODataResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Test custom query options functionality
 */
class CustomOptionsTest extends TestCase
{
    protected $baseUrl;
    protected $client;
    protected $grammar;
    protected $processor;

    public function setUp(): void
    {
        $this->baseUrl = 'https://services.odata.org/V4/TripPinService';
        
        // Create a mock HTTP provider for testing
        $httpProvider = $this->createMockHttpProvider();
        $this->client = new ODataClient($this->baseUrl, null, $httpProvider);
        
        $this->grammar = new Grammar();
        $this->processor = new Processor();
    }

    protected function createMockHttpProvider(): IHttpProvider
    {
        // Create a mock PSR-7 response to satisfy the return type constraint
        $mockPsr7Response = $this->createMock(ResponseInterface::class);
        $mockPsr7Response->method('getBody')->willReturn(
            $this->createMock(\Psr\Http\Message\StreamInterface::class)
        );
        $mockPsr7Response->method('getStatusCode')->willReturn(200);
        $mockPsr7Response->method('getHeaders')->willReturn([]);
        
        $mock = $this->createMock(IHttpProvider::class);
        $mock->method('send')->willReturn($mockPsr7Response);
        $mock->method('sendRequest')->willReturn($mockPsr7Response);
        return $mock;
    }

    public function getBuilder()
    {
        return new Builder(
            $this->client, $this->grammar, $this->processor
        );
    }

    public function testAddOptionWithStringFormat()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')->addOption('timeout=30');
        
        $expected = 'People?timeout=30';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
        $this->assertEquals(['timeout' => '30'], $builder->customOption);
    }

    public function testAddOptionWithArrayFormat()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')->addOption(['timeout' => '30', 'format' => 'minimal']);
        
        $expected = 'People?timeout=30&format=minimal';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
        $this->assertEquals(['timeout' => '30', 'format' => 'minimal'], $builder->customOption);
    }

    public function testAddOptionMergesMultipleCalls()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')
            ->addOption('timeout=30')
            ->addOption('format=minimal')
            ->addOption(['debug' => 'true']);
        
        $expected = 'People?timeout=30&format=minimal&debug=true';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
        $this->assertEquals([
            'timeout' => '30',
            'format' => 'minimal',
            'debug' => 'true'
        ], $builder->customOption);
    }

    public function testAddOptionOverwritesSameKey()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')
            ->addOption('timeout=30')
            ->addOption('timeout=60');
        
        $expected = 'People?timeout=60';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
        $this->assertEquals(['timeout' => '60'], $builder->customOption);
    }

    public function testAddOptionWithMultipleStringValues()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')->addOption('timeout=30,format=minimal');
        
        $expected = 'People?timeout=30&format=minimal';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
        $this->assertEquals(['timeout' => '30', 'format' => 'minimal'], $builder->customOption);
    }

    public function testAddOptionWithSpecialCharacters()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')->addOption(['custom_param' => 'value with spaces', 'encoded' => 'a+b=c']);
        
        $expected = 'People?custom_param=value+with+spaces&encoded=a%2Bb%3Dc';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
    }

    public function testAddOptionWithStandardODataParameters()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')
            ->select('FirstName', 'LastName')
            ->where('FirstName', 'Russell')
            ->addOption('timeout=30');
        
        $expected = 'People?$select=FirstName,LastName&$filter=FirstName eq \'Russell\'&timeout=30';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
    }

    public function testAddOptionIgnoresNullAndEmpty()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')
            ->addOption(null)
            ->addOption('')
            ->addOption('timeout=30');
        
        $expected = 'People?timeout=30';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
        $this->assertEquals(['timeout' => '30'], $builder->customOption);
    }

    public function testAddOptionValidatesKeyStartingWithDollar()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom option key \'$invalid\' cannot start with \'$\' or \'@\'');
        
        $builder = $this->getBuilder();
        $builder->addOption(['$invalid' => 'value']);
    }

    public function testAddOptionValidatesKeyStartingWithAt()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom option key \'@invalid\' cannot start with \'$\' or \'@\'');
        
        $builder = $this->getBuilder();
        $builder->addOption(['@invalid' => 'value']);
    }

    public function testAddOptionValidatesEmptyKey()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom option key cannot be empty');
        
        $builder = $this->getBuilder();
        $builder->addOption(['' => 'value']);
    }

    public function testAddOptionValidatesInvalidKeyCharacters()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a valid identifier');
        
        $builder = $this->getBuilder();
        $builder->addOption(['invalid key!' => 'value']);
    }

    public function testAddOptionValidatesStringFormat()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom option string must contain "=" separator');
        
        $builder = $this->getBuilder();
        $builder->addOption('invalidformat');
    }

    public function testAddOptionValidatesInvalidStringPair()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid custom option format');
        
        $builder = $this->getBuilder();
        $builder->addOption('key1=value1,invalidpair,key2=value2');
    }

    public function testAddOptionValidatesInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Custom option must be a string or array');
        
        $builder = $this->getBuilder();
        $builder->addOption(123);
    }

    public function testAddOptionValidKeyFormats()
    {
        $builder = $this->getBuilder();
        
        // Test various valid key formats
        $validKeys = [
            'simple_key' => 'value1',
            'kebab-case' => 'value2',
            'camelCase' => 'value3',
            '_underscore_start' => 'value4',
            'key123' => 'value5'
        ];
        
        $builder->from('People')->addOption($validKeys);
        
        $this->assertEquals($validKeys, $builder->customOption);
        
        // Should not throw any exceptions
        $requestUri = $builder->toRequest();
        $this->assertStringContains('People?', $requestUri);
    }

    public function testEmptyCustomOptionDoesNotAffectUrl()
    {
        $builder = $this->getBuilder();
        
        $builder->from('People')
            ->select('FirstName')
            ->addOption([]);  // Empty array
        
        $expected = 'People?$select=FirstName';
        $actual = $builder->toRequest();
        
        $this->assertEquals($expected, $actual);
    }

    public function testCompileCompositeCustomOptionInGrammar()
    {
        $grammar = new Grammar();
        
        $options = [
            'timeout' => '30',
            'format' => 'minimal',
            'debug' => 'true'
        ];
        
        $result = $grammar->compileCompositeCustomOption($options);
        
        $expected = 'timeout=30&format=minimal&debug=true';
        $this->assertEquals($expected, $result);
    }

    public function testCompileCompositeCustomOptionWithSpecialCharacters()
    {
        $grammar = new Grammar();
        
        $options = [
            'param with spaces' => 'value with spaces',
            'encoded' => 'a+b=c&d'
        ];
        
        $result = $grammar->compileCompositeCustomOption($options);
        
        $expected = 'param+with+spaces=value+with+spaces&encoded=a%2Bb%3Dc%26d';
        $this->assertEquals($expected, $result);
    }
}