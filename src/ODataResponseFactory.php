<?php

namespace SaintSystems\OData;

class ODataResponseFactory
{
    /**
     * Create an OData response instance based on the given request and HTTP response data.
     *
     * If the Content-Type header indicates a multipart/mixed batch response, an
     * {@see ODataBatchResponse} instance is returned; otherwise, a standard
     * {@see ODataResponse} is created.
     *
     * @param IODataRequest $request    The originating OData request associated with this response.
     * @param string        $body       The raw HTTP response body.
     * @param int           $statusCode The HTTP status code of the response.
     * @param array         $headers    The HTTP response headers, keyed by header name.
     *
     * @return ODataResponse|ODataBatchResponse
     */
    public static function create(IODataRequest $request, string $body, int $statusCode, array $headers = []): IODataResponse
    {
        $contentType = self::getContentType($headers);

        if (
            $contentType !== null &&
            preg_match('/^multipart\/mixed;\s*boundary=(["\']?)([^"\';]+)\1/', $contentType)
        ) {
            return new ODataBatchResponse($request, $body, $statusCode, $headers);
        }

        return new ODataResponse($request, $body, $statusCode, $headers);
    }

    private static function getContentType(array $headers): ?string
    {
        foreach ($headers as $key => $value) {
            if ($value !== null && strtolower($key) === 'content-type') {
                return is_array($value) ? $value[0] : $value;
            }
        }

        return null;
    }
}