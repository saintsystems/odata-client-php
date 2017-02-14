<?php

namespace SaintSystems\OData;

interface IODataRequest
{
    /// <summary>
    /// Gets or sets the content type for the request.
    /// </summary>
    // string ContentType { get; set; }

    // /// <summary>
    // /// Gets the <see cref="HeaderOption"/> collection for the request.
    // /// </summary>
    // IList<HeaderOption> Headers { get; }

    // /// <summary>
    // /// Gets the <see cref="IGraphServiceClient"/> for handling requests.
    // /// </summary>
    // IBaseClient Client { get; }

    // /// <summary>
    // /// Gets or sets the HTTP method string for the request.
    // /// </summary>
    // string Method { get; }

    // /// <summary>
    // /// Gets the URL for the request, without query string.
    // /// </summary>
    // string RequestUrl { get; }

    /// <summary>
    /// Gets the <see cref="QueryOption"/> collection for the request.
    /// </summary>
    //public function getQueryOptions();

    /// <summary>
    /// Gets the <see cref="HttpRequestMessage"/> representation of the request.
    /// </summary>
    /// <returns>The <see cref="HttpRequestMessage"/> representation of the request.</returns>
    public function getHttpRequestMessage();
}
