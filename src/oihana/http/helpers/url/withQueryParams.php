<?php

namespace oihana\http\helpers\url ;

use Psr\Http\Message\UriInterface ;

/**
 * Returns a new {@see UriInterface} with the given query parameters
 * merged into the existing query string.
 *
 * Existing keys are **replaced** by the values from `$params`. Keys
 * not listed in `$params` are preserved. Set a key to `null` in
 * `$params` to remove it (use {@see removeQueryParam()} for a
 * more readable single-key removal).
 *
 * Values follow the same rules as {@see buildQueryString()}:
 * scalars are coerced via `(string)` (booleans → `0`/`1`), arrays
 * are flattened into repeated keys (`?a=1&a=2`).
 *
 * The PSR-7 contract guarantees `withQuery()` returns a NEW
 * instance — the input `$uri` is never mutated.
 *
 * Example:
 * ```php
 * // $uri = https://example.com/path?a=1
 * $next = withQueryParams( $uri , [ 'b' => '2' , 'a' => null ] ) ;
 * // $next  → https://example.com/path?b=2
 * // $uri   unchanged
 * ```
 *
 * @param UriInterface              $uri    The base URI.
 * @param array<string, mixed>      $params The parameters to merge.
 *
 * @return UriInterface
 */
function withQueryParams( UriInterface $uri , array $params ) :UriInterface
{
    $existing = parseQueryString( $uri->getQuery() ) ;

    foreach ( $params as $key => $value )
    {
        if ( $value === null )
        {
            unset( $existing[ $key ] ) ;
            continue ;
        }

        // Wrap scalars so the storage shape stays uniform
        // (`array<string, list<string>>`).
        $existing[ $key ] = is_array( $value )
            ? array_values( array_map( fn( $v ) :string => encodeScalar( $v ) , $value ) )
            : [ encodeScalar( $value ) ] ;
    }

    return $uri->withQuery( buildQueryString( $existing ) ) ;
}
