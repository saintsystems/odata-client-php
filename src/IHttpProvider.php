<?php

namespace SaintSystems\OData;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface IHttpProvider extends ClientInterface
{
    /// <summary>
    /// Gets a serializer for serializing and deserializing JSON objects.
    /// </summary>
    //ISerializer Serializer { get; }

    /**
     * Sends the request using the OData HTTP request message.
     * @param HttpRequestMessage $request The HttpRequestMessage to send.
     *
     * @return ResponseInterface PSR-7 response
     * 
     * @deprecated Use sendRequest() with PSR-7 RequestInterface instead
     */
    public function send(HttpRequestMessage $request): ResponseInterface;

    /// <summary>
    /// Sends the request.
    /// </summary>
    /// <param name="request">The <see cref="HttpRequestMessage"/> to send.</param>
    /// <param name="completionOption">The <see cref="HttpCompletionOption"/> to pass to the <see cref="IHttpProvider"/> on send.</param>
    /// <param name="cancellationToken">The <see cref="CancellationToken"/> for the request.</param>
    /// <returns>The <see cref="HttpResponseMessage"/>.</returns>
    // public function sendAsync(
    //     HttpRequestMessage request,
    //     HttpCompletionOption completionOption,
    //     CancellationToken cancellationToken);
}
