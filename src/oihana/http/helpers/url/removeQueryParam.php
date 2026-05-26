<?php

namespace oihana\http\helpers\url ;

use Psr\Http\Message\UriInterface ;

/**
 * Returns a new {@see UriInterface} with the given query parameter
 * removed.
 *
 * No-op when the parameter is absent — the returned instance still
 * carries a freshly-rebuilt query string (round-tripped through
 * {@see parseQueryString()} + {@see buildQueryString()}, so an
 * unrelated side effect is light query-string normalisation, e.g.
 * `?b=2&a=1` may come back as `?b=2&a=1` reformatted).
 *
 * The PSR-7 contract guarantees `withQuery()` returns a NEW
 * instance — the input `$uri` is never mutated.
 *
 * Convenience wrapper over {@see withQueryParams()} with a single
 * `null` value, kept as a dedicated helper for readability at call
 * sites.
 *
 * Example:
 * ```php
 * // $uri = https://example.com/path?a=1&b=2
 * $next = removeQueryParam( $uri , 'a' ) ;
 * // $next → https://example.com/path?b=2
 * // $uri  unchanged
 * ```
 *
 * @param UriInterface $uri  The base URI.
 * @param string       $name The query parameter to remove.
 *
 * @return UriInterface
 */
function removeQueryParam( UriInterface $uri , string $name ) :UriInterface
{
    $existing = parseQueryString( $uri->getQuery() ) ;

    unset( $existing[ $name ] ) ;

    return $uri->withQuery( buildQueryString( $existing ) ) ;
}
