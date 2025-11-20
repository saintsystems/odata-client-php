<?php

namespace SaintSystems\OData;

class ODataBatchResponse implements IODataResponse
{
    private IODataRequest $request;
    private ?string $body;
    private array $headers;
    private ?string $httpStatusCode;
    private array $responses;
    private ?string $boundary;

    public function __construct(IODataRequest $request, ?string $body = null, ?string $httpStatusCode = null, array $headers = array())
    {
        $this->request = $request;
        $this->body = $body;
        $this->httpStatusCode = $httpStatusCode;
        $this->headers = $headers;
        $this->boundary = $this->extractBoundary();
        $this->responses = $this->parseBatchResponse();
    }

    private function extractBoundary(): ?string
    {
        $contentType = $this->getContentTypeHeader();
        if ($contentType !== null && $contentType !== '' && preg_match('/boundary=(["\']?)([^"\';]+)\1/', $contentType, $matches)) {
            $boundary = $matches[2];
            
            if (strpos(strtolower($boundary), 'batchresponse') !== false) {
                return $boundary;
            }
        }
        return null;
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
        if ($this->body === null || $this->body === '' || $this->boundary === null || $this->boundary === '') {
            return array();
        }

        $responses = array();
        $parts = explode('--' . $this->boundary, $this->body);

        foreach ($parts as $part) {
            $part = trim($part);
            // Skip empty parts and boundary end marker
            if ($part === '' || $part === '--' || $part === "\r\n--" || trim($part, "\r\n-") === '') {
                continue;
            }

            if ($this->isChangesetPart($part)) {
                $changesetResponses = $this->parseChangesetPart($part);
                $responses = array_merge($responses, $changesetResponses);
            } else {
                $response = $this->parseIndividualResponse($part);
                if ($response !== null) {
                    $responses[] = $response;
                }
            }
        }

        return $responses;
    }

    private function isChangesetPart(string $part): bool
    {
        return preg_match('/Content-Type:\s*multipart\/mixed;\s*boundary=["\']?[^"\'\s]*changeset[^"\'\s]*["\']?/i', $part) === 1;
    }

    private function parseChangesetPart(string $part): array
    {
        if (preg_match('/boundary=(["\']?)([^"\'\r\n;]+)\1/i', $part, $matches) !== 1) {
            return array();
        }
        
        $changesetBoundary = $matches[2];
        
        if ($changesetBoundary === '' || strpos(strtolower($changesetBoundary), 'changeset') === false) {
            return array();
        }
        
        // Find where changeset content starts (after empty line)
        $changesetContent = $this->extractChangesetContent($part);
        
        // Parse individual responses within changeset
        $changesetParts = explode('--' . $changesetBoundary, $changesetContent);
        $responses = array();
        
        foreach ($changesetParts as $changesetPart) {
            $changesetPart = trim($changesetPart);
            if ($changesetPart === '' || $changesetPart === '--' || trim($changesetPart, "\r\n-") === '') {
                continue;
            }
            
            $response = $this->parseIndividualResponse($changesetPart);
            if ($response !== null) {
                $responses[] = $response;
            }
        }
        
        return $responses;
    }

    private function extractChangesetContent(string $part): string
    {
        $lines = explode("\n", $part);
        $contentStarted = false;
        $content = array();
        
        foreach ($lines as $line) {
            $line = rtrim($line, "\r");
            
            // Skip until we find an empty line (end of changeset headers)
            if (!$contentStarted) {
                if (trim($line) === '') {
                    $contentStarted = true;
                }
                continue;
            }
            
            $content[] = $line;
        }
        
        return implode("\n", $content);
    }

    private function parseIndividualResponse(string $part): ?ODataResponse
    {
        $lines = explode("\n", $part);
        $inHeaders = true;
        $responseHeaders = array();
        $responseBody = '';
        $statusCode = null;
        $foundHttpResponse = false;

        foreach ($lines as $line) {
            $line = rtrim($line, "\r");
            
            if ($inHeaders) {
                if (trim($line) === '') {
                    // Only switch to body parsing if we've found an HTTP response line
                    if ($foundHttpResponse) {
                        $inHeaders = false;
                    }
                    continue;
                }
                
                if (strpos($line, 'HTTP/') === 0) {
                    $statusParts = explode(' ', $line, 3);
                    $statusCode = (array_key_exists(1, $statusParts) && $statusParts[1] !== null) ? $statusParts[1] : (string)HttpStatusCode::OK;
                    $foundHttpResponse = true;
                    continue;
                }
                
                // Only parse headers after we've found the HTTP response line
                if ($foundHttpResponse && strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $responseHeaders[trim($key)] = trim($value);
                }
            } else {
                $responseBody .= $line . "\n";
            }
        }

        $responseBody = trim($responseBody);
        
        if ($statusCode !== null) {
            return new ODataResponse($this->request, $responseBody, $statusCode, $responseHeaders);
        }
        
        return null;
    }

    public function getBody(): array
    {
        $bodies = array();
        foreach ($this->responses as $response) {
            $bodies[] = $response->getBody();
        }
        return $bodies;
    }

    public function getRawBody(): ?string
    {
        return $this->body;
    }

    public function getStatus(): ?string
    {
        return $this->httpStatusCode;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getResponses(): array
    {
        return $this->responses;
    }

    public function getResponse(int $index): ?ODataResponse
    {
        return (array_key_exists($index, $this->responses) && $this->responses[$index] !== null) ? $this->responses[$index] : null;
    }
}