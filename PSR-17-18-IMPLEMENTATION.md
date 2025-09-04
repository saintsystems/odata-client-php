# PSR-17/PSR-18 Implementation

This branch implements PSR-17 (HTTP Factories) and PSR-18 (HTTP Client) standards for the OData Client PHP library.

## Changes Made

### 1. Updated IHttpProvider Interface
- Now extends `Psr\Http\Client\ClientInterface` for PSR-18 compliance
- Added `sendRequest(RequestInterface $request): ResponseInterface` method
- Marked the old `send(HttpRequestMessage $request)` method as deprecated

### 2. Added PSR Dependencies
- `psr/http-client` ^1.0 - PSR-18 HTTP Client interface
- `psr/http-factory` ^1.0 - PSR-17 HTTP Factory interfaces  
- `psr/http-message` ^1.0 || ^2.0 - PSR-7 HTTP Message interfaces

### 3. Updated HTTP Providers
- **GuzzleHttpProvider**: Added PSR-18 `sendRequest()` method implementation
- **Psr17HttpProvider**: Added PSR-18 `sendRequest()` method implementation
- Both providers maintain backward compatibility with the existing `send()` method

### 4. New Classes
- **HttpRequestBuilder**: Converts OData `HttpRequestMessage` objects to PSR-7 requests
- **HttpClientException**: PSR-18 compliant exception class

## Benefits

1. **Standards Compliance**: Full compliance with PSR-17 and PSR-18 standards
2. **Interoperability**: Any PSR-18 compliant HTTP client can now be used with this library
3. **Future Proof**: Following PHP-FIG standards ensures long-term compatibility
4. **Backward Compatibility**: Existing code continues to work with deprecated methods

## Migration Guide

### For Library Users
No immediate changes required. The existing `send()` method continues to work but is marked as deprecated. 

In future versions, you should migrate to using PSR-18 compliant HTTP clients directly.

### For HTTP Provider Implementers
New providers should implement both methods:
- `send(HttpRequestMessage $request): ResponseInterface` (for backward compatibility)
- `sendRequest(RequestInterface $request): ResponseInterface` (PSR-18 standard)

## Example Usage

```php
// Using the existing interface (deprecated)
$provider = new GuzzleHttpProvider();
$response = $provider->send($httpRequestMessage);

// Using PSR-18 interface (recommended)
$psrRequest = $requestFactory->createRequest('GET', 'https://api.example.com');
$response = $provider->sendRequest($psrRequest);
```

## Testing

All existing tests pass without modification, confirming backward compatibility is maintained.