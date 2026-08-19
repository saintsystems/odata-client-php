<?php

namespace SaintSystems\OData;

use SaintSystems\OData\Exception\ODataException;

class ODataBatchResponse implements IODataResponse
{
    private IODataRequest $request;
    private ?string $body;
    /**
     * @var array<string, string|array<int, string>>
     */
    private array $headers;
    private int $httpStatusCode;
    /**
     * @var array<int, ODataResponse>
     */
    private array $responses;
    private string $boundary;

    /**
     * @throws ODataException
     */
    public function __construct(IODataRequest $request, string $body, int $httpStatusCode, array $headers = [])
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;
        $this->boundary = $this->extractBoundary();
        $this->responses = $this->parseBatchResponse();
    }

    /**
     * @throws ODataException
     */
    private function extractBoundary(): string
    {
        $contentType = $this->getContentTypeHeader();
        if ($contentType !== null && preg_match('/^multipart\/mixed;\s*boundary=(["\']?)([^"\';]+)\1/', $contentType, $matches)) {
            return $matches[2];
        }

        if ($contentType === null) {
            throw new ODataException(
                'No boundary found in batch response content-type header (content-type header is missing).'
            );
        }
        throw new ODataException('No boundary found in batch response content-type header: ' . $contentType);
    }

    private function getContentTypeHeader(): ?string
    {
        foreach ($this->headers as $key => $value) {
            if (strtolower($key) === 'content-type') {
                return is_array($value) ? $value[0] : $value;
            }
        }
        return null;
    }

    private function parseBatchResponse(): array
    {
        if ($this->body === null || $this->body === '') {
            return [];
        }

        $responses = [];
        $parts = explode('--' . $this->boundary, $this->body);

        foreach ($parts as $part) {
            $part = trim($part);
            // Skip empty parts and boundary end marker
            if ('' === $part || '--' === $part) {
                continue;
            }

            $changesetBoundary = $this->extractChangesetBoundary($part);
            if (null === $changesetBoundary) {
                $responses[] = $this->parseIndividualResponse($part);
            } else {
                $changesetResponses = $this->parseChangesetPart($part, $changesetBoundary);
                array_push($responses, ...$changesetResponses);
            }
        }

        return $responses;
    }

    private function extractChangesetBoundary(string $part): ?string
    {
        if (preg_match('/^Content-Type:\s*multipart\/mixed;\s*boundary=["\']?([^"\'\s;]+)["\']?/i', $part, $matches)) {
            return $matches[1];
        }
        return null;
    }

    private function parseChangesetPart(string $part, string $changesetBoundary): array
    {
        // Find where changeset content starts (after empty line)
        $changesetContent = $this->extractChangesetContent($part);

        // Parse individual responses within changeset
        $changesetParts = explode('--' . $changesetBoundary, $changesetContent);
        $responses = [];

        foreach ($changesetParts as $changesetPart) {
            $changesetPart = trim($changesetPart);
            if ('' === $changesetPart || '--' === $changesetPart) {
                continue;
            }

            $responses[] = $this->parseIndividualResponse($changesetPart);
        }

        return $responses;
    }

    private function extractChangesetContent(string $part): string
    {
        $separator = $this->detectHeaderSeparator($part);

        $changesetParts = explode($separator, $part, 2);

        return $changesetParts[1];
    }

    /**
     * @throws ODataException
     */
    private function detectHeaderSeparator(string $part): string
    {
        if (strpos($part, "\r\n\r\n") !== false) {
            return "\r\n\r\n";
        }
        if (strpos($part, "\n\n") !== false) {
            return "\n\n";
        }
        throw new ODataException('No header/body separator found in changeset part: ' . $part);
    }

    private function parseIndividualResponse(string $part): ODataResponse
    {
        $separator = $this->detectHeaderSeparator($part);

        $responseParts = explode($separator, $part, 3);

        if (count($responseParts) < 2) {
            throw new ODataException('Unexpected header format in response part: ' . $part);
        }

        $responseHeaders = $responseParts[0];
        $httpHeaders = $responseParts[1];
        $responseBody = $responseParts[2] ?? '';

        $responseHeadersResult = $this->parseHttpHeaders($responseHeaders);

        $httpHeadersResult = $this->parseHttpHeaders($httpHeaders);
        $responseHeaders = $httpHeadersResult['headers'];
        $statusCode = $httpHeadersResult['statusCode'];

        if (null === $statusCode) {
            throw new ODataException('No http status code found in response part: ' . $part);
        }

        if (array_key_exists('Content-ID', $responseHeadersResult['headers'])) {
            $responseHeaders['Content-ID'] = $responseHeadersResult['headers']['Content-ID'];
        }

        return new ODataResponse($this->request, $responseBody, $statusCode, $responseHeaders);
    }

    /**
     * Parse HTTP headers with support for multi-line headers (header folding)
     *
     * @param string $headerString Raw HTTP header block
     * @return array{statusCode: int|null, statusText: string, headers: array<string, string|array<int, string>>} Parsed headers with status code, status text, and header key-value pairs
     */
    private function parseHttpHeaders(string $headerString): array
    {
        // Unfold headers: replace CRLF followed by whitespace with a single space
        $headerString = preg_replace('/\r?\n[ \t]+/', ' ', $headerString);

        $lines = explode("\n", $headerString);
        $result = [
            'statusCode' => null,
            'statusText' => '',
            'headers' => []
        ];

        foreach ($lines as $index => $line) {
            $line = rtrim($line, "\r");

            // Try to parse first line as status line (e.g., "HTTP/1.1 412 Precondition Failed")
            if (0 === $index && preg_match('/^HTTP\/\d\.\d\s+(\d{3})(\s+(.*))?$/', $line, $matches)) {
                $result['statusCode'] = (int)$matches[1];
                $result['statusText'] = trim($matches[3] ?? '');
                continue;
            }

            // Skip empty lines
            if (trim($line) === '') {
                continue;
            }

            // Parse header line
            if (strpos($line, ':') !== false) {
                [$name, $value] = explode(':', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Store multiple headers with same name as array
                if (array_key_exists($name, $result['headers'])) {
                    if (!is_array($result['headers'][$name])) {
                        $result['headers'][$name] = [$result['headers'][$name]];
                    }
                    $result['headers'][$name][] = $value;
                } else {
                    $result['headers'][$name] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Get the decoded bodies of all responses in the batch
     *
     * @return array<int, array> Array of decoded response bodies, where each element is the JSON-decoded body
     *                           of an individual response in the batch. Returns empty array if no responses.
     */
    public function getBody(): array
    {
        $bodies = [];
        foreach ($this->responses as $response) {
            $bodies[] = $response->getBody();
        }
        return $bodies;
    }

    public function getRawBody(): ?string
    {
        return $this->body;
    }

    public function getStatus(): int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the headers of the batch response
     *
     * @return array<string, string|array<int, string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get all individual responses in the batch
     *
     * @return array<int, ODataResponse>
     */
    public function getResponses(): array
    {
        return $this->responses;
    }

    public function getResponse(int $index): ?ODataResponse
    {
        return $this->responses[$index] ?? null;
    }

    public function getResponseByContentId(string $contentId): ?ODataResponse
    {
        foreach ($this->responses as $response) {
            foreach ($response->getHeaders() as $name => $value) {
                if (strtolower($name) !== 'content-id') {
                    continue;
                }

                if (is_array($value) ? in_array($contentId, $value, true) : $value === $contentId) {
                    return $response;
                }
            }
        }

        return null;
    }
}
