<?php

declare( strict_types = 1 );

namespace oihana\http\helpers\signatures ;

use InvalidArgumentException ;

use function oihana\http\helpers\url\buildQueryString ;
use function oihana\http\helpers\url\normalizeUrl ;
use function oihana\http\helpers\url\parseQueryString ;
use function oihana\http\helpers\url\reassembleUrl ;

/**
 * Signs a URL with an HMAC keyed by `$secret`, returning the URL
 * with a `sig=` query parameter appended (and `exp=` when a TTL
 * is provided).
 *
 * Designed for the common pattern of unguessable, time-bounded URLs
 * — pre-signed downloads, password-reset links, magic-login
 * tokens, S3-style object URLs.
 *
 * Flow:
 * 1. Any existing `sig` / `exp` parameters are stripped (idempotent
 *    re-signing).
 * 2. When `$ttlSeconds !== null`, an `exp` parameter is added with
 *    the absolute Unix timestamp `time() + $ttlSeconds`.
 * 3. The URL is canonicalised via {@see normalizeUrl()} —
 *    scheme/host lowercased, default port dropped, query keys
 *    sorted alphabetically. This is the **signing payload**.
 * 4. The HMAC of the canonical URL (raw binary) is computed and
 *    base64url-encoded (without padding) into the `sig` value.
 * 5. `sig` is appended to the query and the result is
 *    re-normalised so the final URL is itself canonical.
 *
 * Verify with {@see verifySignedUrl()}, which mirrors the flow
 * exactly.
 *
 * Example:
 * ```php
 * $url = signUrl(
 *     'https://api.example.com/files/42?download=1' ,
 *     $secret ,
 *     ttlSeconds: 600 ,  // 10 minutes
 * ) ;
 * // https://api.example.com/files/42?download=1&exp=1767225600&sig=…
 * ```
 *
 * @param string      $url        The URL to sign. The query string
 *                                may already carry `sig` / `exp`
 *                                — they will be stripped before
 *                                signing.
 * @param string      $secret     The shared secret. Must not be
 *                                empty.
 * @param int|null    $ttlSeconds When non-null, an `exp` parameter
 *                                is added at `time() + $ttlSeconds`.
 *                                `null` produces a URL that never
 *                                expires (the signature alone gates
 *                                access).
 * @param string      $algo       The hash algorithm passed to
 *                                `hash_hmac()`. Defaults to
 *                                `'sha256'`.
 *
 * @return string The signed URL.
 *
 * @throws InvalidArgumentException When `$secret` is empty or
 *                                  `$algo` is not a known
 *                                  `hash_hmac` algorithm.
 */
function signUrl( string $url , string $secret , ?int $ttlSeconds = null , string $algo = 'sha256' ) :string
{
    if ( $secret === '' )
    {
        throw new InvalidArgumentException( 'signUrl(): the HMAC secret must not be empty.' ) ;
    }

    if ( !in_array( $algo , hash_hmac_algos() , true ) )
    {
        throw new InvalidArgumentException
        (
            sprintf( 'signUrl(): unknown hash algorithm "%s".' , $algo )
        ) ;
    }

    $parts = parse_url( $url ) ;

    if ( $parts === false )
    {
        throw new InvalidArgumentException
        (
            sprintf( 'signUrl(): unparseable URL "%s".' , $url )
        ) ;
    }

    $query = parseQueryString( $parts[ 'query' ] ?? '' ) ;

    // Strip existing sig/exp so the function is idempotent on
    // already-signed URLs.
    unset( $query[ 'sig' ] , $query[ 'exp' ] ) ;

    if ( $ttlSeconds !== null )
    {
        $query[ 'exp' ] = [ (string) ( time() + $ttlSeconds ) ] ;
    }

    $parts[ 'query' ] = buildQueryString( $query ) ;

    // Canonical signing payload — re-normalising sorts query keys.
    $canonical = normalizeUrl( reassembleUrl( $parts ) ) ;

    $sig = base64UrlEncode( hash_hmac( $algo , $canonical , $secret , true ) ) ;

    // Add sig and re-normalise so the final URL is canonical.
    $query[ 'sig' ] = [ $sig ] ;
    $parts[ 'query' ] = buildQueryString( $query ) ;

    return normalizeUrl( reassembleUrl( $parts ) ) ;
}

/**
 * Encodes binary data with base64url (RFC 4648 §5) — `+` → `-`,
 * `/` → `_`, padding stripped.
 *
 * Internal helper. Not part of the public API.
 *
 * @internal
 *
 * @param string $binary
 *
 * @return string
 */
function base64UrlEncode( string $binary ) :string
{
    return rtrim( strtr( base64_encode( $binary ) , '+/' , '-_' ) , '=' ) ;
}

/**
 * Decodes a base64url-encoded string (RFC 4648 §5).
 *
 * Internal helper. Not part of the public API.
 *
 * @internal
 *
 * @param string $value
 *
 * @return string|false `false` on invalid input.
 */
function base64UrlDecode( string $value ) :string|false
{
    $padded = $value . str_repeat( '=' , ( 4 - strlen( $value ) % 4 ) % 4 ) ;
    return base64_decode( strtr( $padded , '-_' , '+/' ) , true ) ;
}
