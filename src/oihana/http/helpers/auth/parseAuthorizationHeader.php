<?php

namespace oihana\http\helpers\auth ;

use oihana\enums\http\AuthScheme ;
use oihana\http\enums\AuthorizationField ;

/**
 * Parses an HTTP `Authorization` (or `Proxy-Authorization`) header
 * value into a structured `{scheme, credentials}` tuple.
 *
 * The header carries an authentication scheme name (the first
 * whitespace-delimited token, RFC 7235 §2.1) followed by the
 * scheme-specific credentials. This helper splits on the first
 * whitespace and normalises the scheme to the canonical casing
 * carried by `oihana\enums\http\AuthScheme` when it is recognised
 * (e.g. `'BEARER'` → `'Bearer'`, `'basic'` → `'Basic'`). Unknown
 * schemes are preserved as-is.
 *
 * Returns `null` when the input is empty / whitespace-only.
 *
 * The credentials are returned verbatim — including the trailing
 * `,key=value` pairs of schemes like Digest. Decoding the
 * credentials (base64 for `Basic`, JWT for `Bearer`, …) is left
 * to the scheme-specific consumer ({@see getBasicAuth()},
 * {@see getBearerToken()}).
 *
 * Example:
 * ```php
 * parseAuthorizationHeader( 'Bearer eyJhbGci.eyJzdWIi.sig' ) ;
 * // [ 'scheme' => 'Bearer' , 'credentials' => 'eyJhbGci.eyJzdWIi.sig' ]
 *
 * parseAuthorizationHeader( 'BASIC dXNlcjpwYXNz' ) ;
 * // [ 'scheme' => 'Basic' , 'credentials' => 'dXNlcjpwYXNz' ]
 *
 * parseAuthorizationHeader( '' ) ;
 * // null
 * ```
 *
 * @param string $header The raw `Authorization` header value.
 *
 * @return array{scheme: string, credentials: string}|null
 */
function parseAuthorizationHeader( string $header ) :?array
{
    $header = trim( $header ) ;

    if ( $header === '' )
    {
        return null ;
    }

    // RFC 7235 §2.1: `credentials = auth-scheme [ 1*SP ( token68 / #auth-param ) ]`.
    // Splitting on any whitespace run covers both single-SP and weird inputs.
    $segments = preg_split( '/\s+/' , $header , 2 ) ;

    $scheme = $segments[ 0 ] ;

    $credentials = isset( $segments[ 1 ] ) ? ltrim( $segments[ 1 ] ) : '' ;

    return
    [
        AuthorizationField::SCHEME      => canonicaliseAuthScheme( $scheme ) ,
        AuthorizationField::CREDENTIALS => $credentials ,
    ] ;
}

/**
 * Rewrites a scheme token to the canonical casing carried by
 * {@see AuthScheme} when the scheme is recognised (case-insensitive
 * lookup). Unknown schemes are returned untouched.
 *
 * Internal helper. Not part of the public API.
 *
 * @internal
 *
 * @param string $scheme
 *
 * @return string
 */
function canonicaliseAuthScheme( string $scheme ) :string
{
    static $canonicals = null ;

    if ( $canonicals === null )
    {
        $canonicals = [] ;
        foreach ( AuthScheme::enums() as $canonical )
        {
            $canonicals[ strtolower( $canonical ) ] = $canonical ;
        }
    }

    return $canonicals[ strtolower( $scheme ) ] ?? $scheme ;
}
