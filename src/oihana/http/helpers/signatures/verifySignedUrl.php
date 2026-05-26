<?php

declare( strict_types = 1 );

namespace oihana\http\helpers\signatures ;

use function oihana\core\encoding\base64UrlDecode ;
use function oihana\core\encoding\base64UrlEncode ;
use function oihana\http\helpers\url\buildQueryString ;
use function oihana\http\helpers\url\normalizeUrl ;
use function oihana\http\helpers\url\parseQueryString ;
use function oihana\http\helpers\url\reassembleUrl ;

/**
 * Verifies a URL signed by {@see signUrl()}.
 *
 * Constant-time comparison via `hash_equals()` — safe against
 * timing-side-channel attacks.
 *
 * Flow:
 * 1. The `sig` parameter is extracted from the query.
 * 2. If an `exp` parameter is present, the URL is rejected when
 *    `exp <= time()`.
 * 3. `sig` is removed and the URL is canonicalised the same way
 *    {@see signUrl()} did.
 * 4. The expected HMAC is recomputed and compared against the
 *    incoming `sig` in constant time.
 *
 * Returns `false` (never throws) for every failure mode: missing
 * `sig`, expired `exp`, malformed base64url, signature mismatch,
 * unparseable URL. Callers can therefore treat the boolean as the
 * sole "allow / deny" signal.
 *
 * Example:
 * ```php
 * if ( !verifySignedUrl( $url , $secret ) )
 * {
 *     return new Response( 401 ) ;
 * }
 * // …serve the signed resource
 * ```
 *
 * @param string $url    The URL to verify (with `sig` / `exp`
 *                       query parameters).
 * @param string $secret The shared secret used by `signUrl()`.
 * @param string $algo   The hash algorithm used at signing time.
 *                       Defaults to `'sha256'` — must match the
 *                       value passed to `signUrl()`.
 *
 * @return bool `true` when the signature is valid AND (if `exp`
 *              is present) the URL has not yet expired.
 */
function verifySignedUrl( string $url , string $secret , string $algo = 'sha256' ) :bool
{
    if ( $secret === '' )
    {
        return false ;
    }

    if ( !in_array( $algo , hash_hmac_algos() , true ) )
    {
        return false ;
    }

    $parts = parse_url( $url ) ;

    if ( $parts === false )
    {
        return false ;
    }

    $query = parseQueryString( $parts[ 'query' ] ?? '' ) ;

    if ( !isset( $query[ 'sig' ][ 0 ] ) || $query[ 'sig' ][ 0 ] === '' )
    {
        return false ;
    }

    $sig = $query[ 'sig' ][ 0 ] ;

    // Reject malformed base64url early.
    if ( base64UrlDecode( $sig ) === false )
    {
        return false ;
    }

    // Expiration check.
    if ( isset( $query[ 'exp' ][ 0 ] ) )
    {
        $exp = $query[ 'exp' ][ 0 ] ;

        if ( !ctype_digit( $exp ) )
        {
            return false ;
        }

        if ( (int) $exp <= time() )
        {
            return false ;
        }
    }

    // Reconstruct the canonical signing payload by removing `sig`.
    unset( $query[ 'sig' ] ) ;

    $parts[ 'query' ] = buildQueryString( $query ) ;
    $canonical        = normalizeUrl( reassembleUrl( $parts ) ) ;

    $expected = base64UrlEncode( hash_hmac( $algo , $canonical , $secret , true ) ) ;

    return hash_equals( $expected , $sig ) ;
}
