<?php

namespace SaintSystems\OData\Exception;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * PSR-18 compliant HTTP client exception
 */
class HttpClientException extends \RuntimeException implements ClientExceptionInterface
{
}