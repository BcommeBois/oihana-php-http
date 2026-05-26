<?php

namespace oihana\http\helpers\url ;

/**
 * Parses an HTTP query string into a `name => list<value>` map.
 *
 * Differences with PHP's native `parse_str()`:
 * - Duplicate keys are **preserved** as a list of values instead of
 *   being silently overwritten. `'a=1&a=2'` decodes to
 *   `['a' => ['1', '2']]` rather than `['a' => '2']`.
 * - Keys are treated as **opaque** strings: the `[]` / `[k]` syntax
 *   used by PHP for nested arrays is NOT interpreted. `'a[]=1&a[]=2'`
 *   decodes to `['a[]' => ['1', '2']]`. Callers that need PHP's
 *   nested-array semantics should keep using `parse_str()`.
 * - Each value is always returned as an array (a `list<string>`),
 *   even when the key occurs only once — predictable type, no
 *   surprise union.
 *
 * Values are URL-decoded using `rawurldecode()` (RFC 3986) by
 * default. To handle `application/x-www-form-urlencoded` payloads
 * where the `+` character means space, pass `$formEncoded = true`.
 *
 * Symmetric with {@see buildQueryString()} — the two roundtrip
 * cleanly.
 *
 * Examples:
 * ```php
 * parseQueryString( 'a=1&b=2' ) ;
 * // [ 'a' => [ '1' ] , 'b' => [ '2' ] ]
 *
 * parseQueryString( 'a=1&a=2&a=3' ) ;
 * // [ 'a' => [ '1' , '2' , '3' ] ]
 *
 * parseQueryString( 'q=hello+world' , true ) ;
 * // [ 'q' => [ 'hello world' ] ]
 *
 * parseQueryString( 'flag' ) ;
 * // [ 'flag' => [ '' ] ]
 *
 * parseQueryString( '' ) ;
 * // []
 * ```
 *
 * @param string $query       The raw query string (with or without
 *                            the leading `?`).
 * @param bool   $formEncoded When `true`, decode using `urldecode()`
 *                            (`+` → space). Defaults to `false` —
 *                            strict RFC 3986 percent decoding.
 *
 * @return array<string, list<string>>
 */
function parseQueryString( string $query , bool $formEncoded = false ) :array
{
    $query = ltrim( $query , '?' ) ;

    if ( $query === '' )
    {
        return [] ;
    }

    $decode = $formEncoded ? 'urldecode' : 'rawurldecode' ;

    $result = [] ;

    foreach ( explode( '&' , $query ) as $pair )
    {
        if ( $pair === '' )
        {
            continue ;
        }

        $eq = strpos( $pair , '=' ) ;

        if ( $eq === false )
        {
            $name  = $decode( $pair ) ;
            $value = '' ;
        }
        else
        {
            $name  = $decode( substr( $pair , 0 , $eq ) ) ;
            $value = $decode( substr( $pair , $eq + 1 ) ) ;
        }

        if ( $name === '' )
        {
            continue ;
        }

        $result[ $name ][] = $value ;
    }

    return $result ;
}
