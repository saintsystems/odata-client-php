<?php

namespace SaintSystems\OData;

class ODataResponseFactory
{
    public static function create(IODataRequest $request,  ?string $body = null, ?string $statusCode = null, array $headers = array())
    {
        $contentType = self::getContentType($headers);
        
        // Batch response detection - Microsoft uses multipart/mixed with batchresponse_ boundary
        if ($contentType !== null &&
            strpos($contentType, 'multipart/mixed') === 0 &&
            strpos($contentType, 'boundary=batchresponse_') !== false) {
            return new ODataBatchResponse($request, $body, $statusCode, $headers);
        }
        
        return new ODataResponse($request, $body, $statusCode, $headers);
    }
    
    private static function getContentType(array $headers): ?string
    {
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'content-type' && $value !== null) {
                return $value;
            }
        }

        return null;
    }
}