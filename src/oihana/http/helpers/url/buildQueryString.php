<?php

namespace oihana\http\helpers\url ;

/**
 * Builds an HTTP query string from an associative array.
 *
 * Differences with PHP's native `http_build_query()`:
 * - Arrays of values are emitted as **repeated keys**
 *   (`a=1&a=2&a=3`), not as bracketed `a[0]=1&a[1]=2&a[2]=3`.
 *   This matches the wire format used by most REST APIs and
 *   keeps the function symmetric with {@see parseQueryString()}.
 * - `bool` values are emitted as the literal `0` / `1`.
 * - `null` values are emitted as a bare key (no `=` sign) —
 *   useful for flag-style parameters like `?debug`.
 * - Keys and scalar values are encoded with `rawurlencode()`
 *   (RFC 3986) by default. Pass `$rfc3986 = false` to switch to
 *   `urlencode()` (`application/x-www-form-urlencoded`, `+` for
 *   space) — needed for form payloads, rare for URLs.
 *
 * Empty input returns the empty string.
 *
 * Symmetric with {@see parseQueryString()} — `buildQueryString(
 * parseQueryString($q) )` roundtrips cleanly.
 *
 * Examples:
 * ```php
 * buildQueryString( [ 'a' => '1' , 'b' => '2' ] ) ;
 * // 'a=1&b=2'
 *
 * buildQueryString( [ 'a' => [ '1' , '2' , '3' ] ] ) ;
 * // 'a=1&a=2&a=3'
 *
 * buildQueryString( [ 'q' => 'hello world' ] ) ;
 * // 'q=hello%20world'           (RFC 3986)
 *
 * buildQueryString( [ 'q' => 'hello world' ] , false ) ;
 * // 'q=hello+world'             (form-encoded)
 *
 * buildQueryString( [ 'verbose' => true , 'debug' => null ] ) ;
 * // 'verbose=1&debug'
 *
 * buildQueryString( [] ) ;
 * // ''
 * ```
 *
 * @param array<int|string, mixed> $params  The parameters to
 *                                          encode. Arrays of
 *                                          scalars are flattened
 *                                          into repeated keys.
 * @param bool                     $rfc3986 When `true` (default),
 *                                          use `rawurlencode`
 *                                          (RFC 3986). When
 *                                          `false`, use `urlencode`
 *                                          (form-encoded).
 *
 * @return string The encoded query string (without the leading `?`).
 */
function buildQueryString( array $params , bool $rfc3986 = true ) :string
{
    if ( $params === [] )
    {
        return '' ;
    }

    $encode = $rfc3986 ? 'rawurlencode' : 'urlencode' ;

    $parts = [] ;

    foreach ( $params as $key => $value )
    {
        $encodedKey = $encode( (string) $key ) ;

        if ( is_array( $value ) )
        {
            foreach ( $value as $element )
            {
                $parts[] = $encodedKey . '=' . $encode( encodeScalar( $element ) ) ;
            }
            continue ;
        }

        if ( $value === null )
        {
            $parts[] = $encodedKey ;
            continue ;
        }

        $parts[] = $encodedKey . '=' . $encode( encodeScalar( $value ) ) ;
    }

    return implode( '&' , $parts ) ;
}

/**
 * Coerces a scalar value into the string form expected on the wire.
 *
 * Internal helper. Not part of the public API.
 *
 * @internal
 *
 * @param mixed $value
 *
 * @return string
 */
function encodeScalar( mixed $value ) :string
{
    if ( is_bool( $value ) )
    {
        return $value ? '1' : '0' ;
    }

    return (string) $value ;
}
