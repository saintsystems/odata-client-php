<?php

namespace SaintSystems\OData\Tests;

use PHPUnit\Framework\TestCase;
use SaintSystems\OData\ODataBatchResponse;
use SaintSystems\OData\ODataResponse;
use SaintSystems\OData\IODataRequest;

class ODataBatchResponseTest extends TestCase
{

    public function testBatchResponseExample()
    {
        $boundary = 'batchresponse_01346794-f2e2-4d45-8cc2-f97e09fe8916';
        
        $body = <<<EOD
--$boundary
Content-Type: application/http
Content-Transfer-Encoding: binary

HTTP/1.1 204 No Content
OData-Version: 4.0
Location: [Organization Uri]/api/data/v9.2/tasks(00aa00aa-bb11-cc22-dd33-44ee44ee44ee)
OData-EntityId: [Organization Uri]/api/data/v9.2/tasks(00aa00aa-bb11-cc22-dd33-44ee44ee44ee)

--$boundary
Content-Type: application/http
Content-Transfer-Encoding: binary

HTTP/1.1 204 No Content
OData-Version: 4.0
Location: [Organization Uri]/api/data/v9.2/tasks(11bb11bb-cc22-dd33-ee44-55ff55ff55ff)
OData-EntityId: [Organization Uri]/api/data/v9.2/tasks(11bb11bb-cc22-dd33-ee44-55ff55ff55ff)

--$boundary
Content-Type: application/http
Content-Transfer-Encoding: binary

HTTP/1.1 200 OK
Content-Type: application/json; odata.metadata=minimal; odata.streaming=true
OData-Version: 4.0

{
  "@odata.context": "[Organization Uri]/api/data/v9.2/\$metadata#tasks(subject)",
  "value": [
    {
      "@odata.etag": "W/\"77180907\"",
      "subject": "Task 1 in batch",
      "activityid": "00aa00aa-bb11-cc22-dd33-44ee44ee44ee"
    },
    {
      "@odata.etag": "W/\"77180908\"",
      "subject": "Task 2 in batch",
      "activityid": "11bb11bb-cc22-dd33-ee44-55ff55ff55ff"
    }
  ]
}
--$boundary--

EOD;

        $headers = [
            'Content-Type' => "multipart/mixed; boundary=$boundary",
            'OData-Version' => '4.0'
        ];
        
        $request = $this->createMock(IODataRequest::class);
        $batchResponse = new ODataBatchResponse($request, $body, '200', $headers);
        
        $responses = $batchResponse->getResponses();
        $this->assertCount(3, $responses);
        
        // Verify status codes
        $this->assertSame('204', $responses[0]->getStatus());
        $this->assertSame('204', $responses[1]->getStatus());
        $this->assertSame('200', $responses[2]->getStatus());
        
        // Verify response headers
        $headers1 = $responses[0]->getHeaders();
        $this->assertSame('4.0', $headers1['OData-Version']);
        $this->assertSame('[Organization Uri]/api/data/v9.2/tasks(00aa00aa-bb11-cc22-dd33-44ee44ee44ee)', $headers1['Location']);
        $this->assertSame('[Organization Uri]/api/data/v9.2/tasks(00aa00aa-bb11-cc22-dd33-44ee44ee44ee)', $headers1['OData-EntityId']);
        
        $headers2 = $responses[1]->getHeaders();
        $this->assertSame('4.0', $headers2['OData-Version']);
        $this->assertSame('[Organization Uri]/api/data/v9.2/tasks(11bb11bb-cc22-dd33-ee44-55ff55ff55ff)', $headers2['Location']);
        $this->assertSame('[Organization Uri]/api/data/v9.2/tasks(11bb11bb-cc22-dd33-ee44-55ff55ff55ff)', $headers2['OData-EntityId']);
        
        $headers3 = $responses[2]->getHeaders();
        $this->assertSame('application/json; odata.metadata=minimal; odata.streaming=true', $headers3['Content-Type']);
        $this->assertSame('4.0', $headers3['OData-Version']);
        
        // Verify raw body content
        $expectedJson = <<<'JSON'
{
  "@odata.context": "[Organization Uri]/api/data/v9.2/$metadata#tasks(subject)",
  "value": [
    {
      "@odata.etag": "W/\"77180907\"",
      "subject": "Task 1 in batch",
      "activityid": "00aa00aa-bb11-cc22-dd33-44ee44ee44ee"
    },
    {
      "@odata.etag": "W/\"77180908\"",
      "subject": "Task 2 in batch",
      "activityid": "11bb11bb-cc22-dd33-ee44-55ff55ff55ff"
    }
  ]
}
JSON;
        $this->assertSame($expectedJson, $responses[2]->getRawBody());
        
        // Verify expected structure
        $jsonResponse = $responses[2];
        $body = $jsonResponse->getBody();
        $this->assertSame('Task 1 in batch', $body['value'][0]['subject']);
        $this->assertSame('Task 2 in batch', $body['value'][1]['subject']);
    }

    public function testChangesetBatchResponse()
    {
        $boundary = 'batchresponse_f27ef42d-51b0-4685-bac9-f468f844de2f';
        $changesetBoundary = 'changesetresponse_5c9b9207-0a2e-4a4f-9b7a-8b8b8b8b8b8b';
        
        $body = <<<EOD
--$boundary
Content-Type: multipart/mixed; boundary=$changesetBoundary

--$changesetBoundary
Content-Type: application/http
Content-Transfer-Encoding: binary
Content-ID: 1

HTTP/1.1 204 No Content
OData-Version: 4.0
Location: [Organization URI]/api/data/v9.2/accounts(55ff55ff-aa66-bb77-cc88-99dd99dd99dd)
OData-EntityId: [Organization URI]/api/data/v9.2/accounts(55ff55ff-aa66-bb77-cc88-99dd99dd99dd)

--$changesetBoundary
Content-Type: application/http
Content-Transfer-Encoding: binary
Content-ID: 2

HTTP/1.1 204 No Content
OData-Version: 4.0
Location: [Organization URI]/api/data/v9.2/contacts(66aa66aa-bb77-cc88-dd99-00ee00ee00ee)
OData-EntityId: [Organization URI]/api/data/v9.2/contacts(66aa66aa-bb77-cc88-dd99-00ee00ee00ee)

--$changesetBoundary--
--$boundary--

EOD;

        $headers = [
            'Content-Type' => "multipart/mixed; boundary=$boundary",
            'OData-Version' => '4.0'
        ];
        
        $request = $this->createMock(IODataRequest::class);
        $batchResponse = new ODataBatchResponse($request, $body, '200', $headers);
        
        $responses = $batchResponse->getResponses();
        
        $this->assertCount(2, $responses);
        
        $response1 = $responses[0];
        $this->assertInstanceOf(ODataResponse::class, $response1);
        $this->assertSame('204', $response1->getStatus());
        
        $headers1 = $response1->getHeaders();
        $this->assertSame('4.0', $headers1['OData-Version']);
        $this->assertSame('[Organization URI]/api/data/v9.2/accounts(55ff55ff-aa66-bb77-cc88-99dd99dd99dd)', $headers1['Location']);
        $this->assertSame('[Organization URI]/api/data/v9.2/accounts(55ff55ff-aa66-bb77-cc88-99dd99dd99dd)', $headers1['OData-EntityId']);
        $this->assertSame('', $response1->getRawBody());
        
        $response2 = $responses[1];
        $this->assertInstanceOf(ODataResponse::class, $response2);
        $this->assertSame('204', $response2->getStatus());
        
        $headers2 = $response2->getHeaders();
        $this->assertSame('4.0', $headers2['OData-Version']);
        $this->assertSame('[Organization URI]/api/data/v9.2/contacts(66aa66aa-bb77-cc88-dd99-00ee00ee00ee)', $headers2['Location']);
        $this->assertSame('[Organization URI]/api/data/v9.2/contacts(66aa66aa-bb77-cc88-dd99-00ee00ee00ee)', $headers2['OData-EntityId']);
        $this->assertSame('', $response2->getRawBody());
    }

    public function testBatchResponseWithMixedSuccessAndFailure()
    {
        $boundary = 'batchresponse_a1b2c3d4-e5f6-7890-abcd-ef1234567890';
        
        $body = <<<EOD
--$boundary
Content-Type: application/http
Content-Transfer-Encoding: binary

HTTP/1.1 400 Bad Request
Content-Type: application/json; odata.metadata=minimal
OData-Version: 4.0

{
  "error": {
    "code": "0x80040237",
    "message": "Subject length exceeded maximum of 200 characters",
    "innererror": {
      "message": "Subject length exceeded maximum of 200 characters",
      "type": "System.ArgumentException"
    }
  }
}

--$boundary
Content-Type: application/http
Content-Transfer-Encoding: binary

HTTP/1.1 204 No Content
OData-Version: 4.0
Location: [Organization Uri]/api/data/v9.2/tasks(22cc22cc-dd33-ee44-ff55-66aa66aa66aa)
OData-EntityId: [Organization Uri]/api/data/v9.2/tasks(22cc22cc-dd33-ee44-ff55-66aa66aa66aa)

--$boundary
Content-Type: application/http
Content-Transfer-Encoding: binary

HTTP/1.1 204 No Content
OData-Version: 4.0
Location: [Organization Uri]/api/data/v9.2/tasks(33dd33dd-ee44-ff55-aa66-77bb77bb77bb)
OData-EntityId: [Organization Uri]/api/data/v9.2/tasks(33dd33dd-ee44-ff55-aa66-77bb77bb77bb)

--$boundary
Content-Type: application/http
Content-Transfer-Encoding: binary

HTTP/1.1 404 Not Found
Content-Type: application/json; odata.metadata=minimal
OData-Version: 4.0

{
  "error": {
    "code": "0x80040217",
    "message": "The requested resource does not exist",
    "innererror": {
      "message": "Entity with id '99zz99zz-invalid-entity-id' does not exist",
      "type": "Microsoft.Xrm.Sdk.InvalidOperationException"
    }
  }
}

--$boundary--

EOD;

        $headers = [
            'Content-Type' => "multipart/mixed; boundary=$boundary",
            'OData-Version' => '4.0',
            'Prefer' => 'odata.continue-on-error'
        ];
        
        $request = $this->createMock(IODataRequest::class);
        $batchResponse = new ODataBatchResponse($request, $body, '200', $headers);
        
        $responses = $batchResponse->getResponses();
        $this->assertCount(4, $responses);
        
        // Verify status codes
        $this->assertSame('400', $responses[0]->getStatus());
        $this->assertSame('204', $responses[1]->getStatus());
        $this->assertSame('204', $responses[2]->getStatus());
        $this->assertSame('404', $responses[3]->getStatus());
        
        // Verify headers
        $errorHeaders1 = $responses[0]->getHeaders();
        $this->assertSame('application/json; odata.metadata=minimal', $errorHeaders1['Content-Type']);
        $this->assertSame('4.0', $errorHeaders1['OData-Version']);

        $successHeaders2 = $responses[1]->getHeaders();
        $this->assertSame('4.0', $successHeaders2['OData-Version']);
        $this->assertSame('[Organization Uri]/api/data/v9.2/tasks(22cc22cc-dd33-ee44-ff55-66aa66aa66aa)', $successHeaders2['Location']);
        $this->assertSame('[Organization Uri]/api/data/v9.2/tasks(22cc22cc-dd33-ee44-ff55-66aa66aa66aa)', $successHeaders2['OData-EntityId']);

        $successHeaders3 = $responses[2]->getHeaders();
        $this->assertSame('4.0', $successHeaders3['OData-Version']);
        $this->assertSame('[Organization Uri]/api/data/v9.2/tasks(33dd33dd-ee44-ff55-aa66-77bb77bb77bb)', $successHeaders3['Location']);
        $this->assertSame('[Organization Uri]/api/data/v9.2/tasks(33dd33dd-ee44-ff55-aa66-77bb77bb77bb)', $successHeaders3['OData-EntityId']);

        $errorHeaders4 = $responses[3]->getHeaders();
        $this->assertSame('application/json; odata.metadata=minimal', $errorHeaders4['Content-Type']);
        $this->assertSame('4.0', $errorHeaders4['OData-Version']);


        // Verify response bodies
        $expectedError1 = <<<'JSON'
{
  "error": {
    "code": "0x80040237",
    "message": "Subject length exceeded maximum of 200 characters",
    "innererror": {
      "message": "Subject length exceeded maximum of 200 characters",
      "type": "System.ArgumentException"
    }
  }
}
JSON;
        $this->assertSame($expectedError1, $responses[0]->getRawBody());
        
        $expectedError2 = <<<'JSON'
{
  "error": {
    "code": "0x80040217",
    "message": "The requested resource does not exist",
    "innererror": {
      "message": "Entity with id '99zz99zz-invalid-entity-id' does not exist",
      "type": "Microsoft.Xrm.Sdk.InvalidOperationException"
    }
  }
}
JSON;
        $this->assertSame($expectedError2, $responses[3]->getRawBody());

        // Verify expected structure
        $errorResponse1 = $responses[0]->getBody();
        $errorResponse2 = $responses[3]->getBody();
        $this->assertSame('0x80040237', $errorResponse1['error']['code']);
        $this->assertSame('0x80040217', $errorResponse2['error']['code']);
        
        // Test that batch overall status is still 200 OK (continue-on-error)
        $this->assertSame('200', $batchResponse->getStatus());
    }
}