<?php

namespace oihana\http\helpers\url ;

/**
 * Extracts the host of a URL in a normalised, comparison-friendly form.
 *
 * The host returned by `parse_url()` is post-processed so callers get a
 * value ready for comparison, allow-listing or IP validation:
 * - lowercased (`Example.COM` → `example.com`), since hosts are
 *   case-insensitive;
 * - IPv6 brackets removed (`[::1]` → `::1`), so the result feeds
 *   straight into `filter_var()` / {@see isPublicIp()} without further
 *   trimming.
 *
 * Returns `null` when the URL carries no host component — a relative
 * path, an empty string or unparseable input.
 *
 * Note: the bracket-stripped IPv6 form is meant for inspection, not for
 * reinjection into a URL (a bare `::1` is not a valid URL authority).
 * URL reassembly stays the job of `normalizeUrl()`, which keeps the
 * brackets it needs.
 *
 * Examples:
 * ```php
 * getHost( 'https://API.Example.com/path?x=1' ) ; // 'api.example.com'
 * getHost( 'http://localhost:8080' )             ; // 'localhost'
 * getHost( 'http://127.0.0.1' )                  ; // '127.0.0.1'
 * getHost( 'http://[2001:db8::1]:443/x' )        ; // '2001:db8::1'
 * getHost( 'mailto:alice@example.com' )          ; // null (no authority)
 * getHost( '/relative/path' )                    ; // null
 * getHost( '' )                                  ; // null
 * ```
 *
 * @param string $url The URL whose host is extracted.
 *
 * @return string|null The normalised host, or `null` when absent.
 */
function getHost( string $url ) :?string
{
    $host = parse_url( $url , PHP_URL_HOST ) ;

    if( !is_string( $host ) || $host === '' )
    {
        return null ;
    }

    // parse_url() keeps IPv6 literals bracketed: [::1] , [2001:db8::1].
    if( $host[0] === '[' && str_ends_with( $host , ']' ) )
    {
        $host = substr( $host , 1 , -1 ) ;
    }

    return strtolower( $host ) ;
}
