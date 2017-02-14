<?php

namespace SaintSystems\OData;

interface IHttpProvider
{
    /// <summary>
    /// Gets a serializer for serializing and deserializing JSON objects.
    /// </summary>
    //ISerializer Serializer { get; }

    /**
     * Sends the request.
     * @param  HttpRequestMessage $request The HttpRequestMessage to send.
     * @return HttpResponseMessage         The HttpResponseMessage.
     */
    public function send(HttpRequestMessage $request);

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
