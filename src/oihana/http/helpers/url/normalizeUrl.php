<?php

namespace oihana\http\helpers\url ;

/**
 * Returns a canonical form of a URL string, suitable for deduplication,
 * caching and comparison.
 *
 * Normalisations applied (RFC 3986 §6 subset, pragmatic):
 * - **scheme** is lowercased (`HTTPS` → `https`).
 * - **host** is lowercased (`Example.COM` → `example.com`).
 * - **default port** is dropped when it matches the scheme
 *   (`http://example.com:80/` → `http://example.com/`,
 *   `https://example.com:443/` → `https://example.com/`, idem
 *   `ws:80`, `wss:443`, `ftp:21`).
 * - **query keys** are sorted alphabetically and the duplicate-aware
 *   parser ({@see parseQueryString()}) + RFC 3986 encoder
 *   ({@see buildQueryString()}) re-emit the query string in a
 *   stable form.
 * - **fragment** is preserved as-is.
 *
 * Not applied (out of scope, would require a heavier URI library):
 * - percent-decoding of unreserved characters in the path ;
 * - dot-segment resolution (`/a/./b/../c` → `/a/c`) ;
 * - Punycode / IDN normalisation of the host.
 *
 * If the input cannot be parsed by `parse_url`, it is returned
 * untouched (fail-open contract — same approach as
 * {@see canonicalIp()}).
 *
 * Examples:
 * ```php
 * normalizeUrl( 'HTTPS://Example.COM:443/Path?b=2&a=1' ) ;
 * // 'https://example.com/Path?a=1&b=2'
 *
 * normalizeUrl( 'http://example.com:80/' ) ;
 * // 'http://example.com/'
 *
 * normalizeUrl( '/api/v1?z=9&a=1#frag' ) ;
 * // '/api/v1?a=1&z=9#frag'  (no scheme/host to normalise)
 * ```
 *
 * @param string $url The URL to canonicalise.
 *
 * @return string The normalised URL.
 */
function normalizeUrl( string $url ) :string
{
    $parts = parse_url( $url ) ;

    if ( $parts === false )
    {
        return $url ;
    }

    if ( isset( $parts[ 'scheme' ] ) )
    {
        $parts[ 'scheme' ] = strtolower( $parts[ 'scheme' ] ) ;
    }

    if ( isset( $parts[ 'host' ] ) )
    {
        $parts[ 'host' ] = strtolower( $parts[ 'host' ] ) ;
    }

    $defaultPorts =
    [
        'http'  => 80  ,
        'https' => 443 ,
        'ws'    => 80  ,
        'wss'   => 443 ,
        'ftp'   => 21  ,
    ] ;

    if ( isset( $parts[ 'port' ] , $parts[ 'scheme' ] )
        && ( $defaultPorts[ $parts[ 'scheme' ] ] ?? null ) === $parts[ 'port' ] )
    {
        unset( $parts[ 'port' ] ) ;
    }

    if ( isset( $parts[ 'query' ] ) && $parts[ 'query' ] !== '' )
    {
        $query = parseQueryString( $parts[ 'query' ] ) ;
        ksort( $query ) ;
        $parts[ 'query' ] = buildQueryString( $query ) ;
    }

    return reassembleUrl( $parts ) ;
}

/**
 * Reassembles a `parse_url`-shaped associative array back into a
 * URL string.
 *
 * Internal helper. Not part of the public API.
 *
 * @internal
 *
 * @param array<string, string|int> $parts
 *
 * @return string
 */
function reassembleUrl( array $parts ) :string
{
    $url = '' ;

    if ( isset( $parts[ 'scheme' ] ) )
    {
        $url .= $parts[ 'scheme' ] . ':' ;
    }

    if ( isset( $parts[ 'host' ] ) )
    {
        $url .= '//' ;

        if ( isset( $parts[ 'user' ] ) )
        {
            $url .= $parts[ 'user' ] ;
            if ( isset( $parts[ 'pass' ] ) )
            {
                $url .= ':' . $parts[ 'pass' ] ;
            }
            $url .= '@' ;
        }

        $url .= $parts[ 'host' ] ;

        if ( isset( $parts[ 'port' ] ) )
        {
            $url .= ':' . $parts[ 'port' ] ;
        }
    }

    if ( isset( $parts[ 'path' ] ) )
    {
        $url .= $parts[ 'path' ] ;
    }

    if ( isset( $parts[ 'query' ] ) && $parts[ 'query' ] !== '' )
    {
        $url .= '?' . $parts[ 'query' ] ;
    }

    if ( isset( $parts[ 'fragment' ] ) )
    {
        $url .= '#' . $parts[ 'fragment' ] ;
    }

    return $url ;
}
