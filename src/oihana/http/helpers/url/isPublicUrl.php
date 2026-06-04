<?php

namespace oihana\http\helpers\url ;

use function oihana\http\helpers\ips\isPublicIp ;

/**
 * Tells whether a URL points to a host reachable over the public
 * Internet, as opposed to a local or private one.
 *
 * The host is extracted from `$url` with `parse_url()`, then:
 * - `localhost` and any `*.localhost` sub-domain → `false`;
 * - an IP literal (IPv4 or IPv6, with or without the `[...]`
 *   brackets that `parse_url()` keeps around IPv6) is handed to
 *   {@see \oihana\http\helpers\ips\isPublicIp()}, so every loopback,
 *   private (RFC 1918 / RFC 4193) and reserved range → `false`;
 * - any other named host (a FQDN such as `api.example.com`) →
 *   `true`.
 *
 * This is a **syntactic heuristic**: no DNS resolution is performed.
 * A FQDN that resolves to a private address is still reported as
 * public — so this is a routing hint (is an explicit public endpoint
 * required?), not an anti-SSRF guard.
 *
 * A URL with no host component (relative path, empty string,
 * unparseable input) → `false`.
 *
 * Examples:
 * ```php
 * isPublicUrl( 'https://api.example.com' )        ; // true
 * isPublicUrl( 'https://8.8.8.8' )                ; // true
 * isPublicUrl( 'http://localhost' )               ; // false
 * isPublicUrl( 'http://app.localhost:8080' )      ; // false
 * isPublicUrl( 'http://127.0.0.1' )               ; // false (loopback)
 * isPublicUrl( 'http://10.0.0.1' )                ; // false (RFC 1918)
 * isPublicUrl( 'http://[::1]' )                   ; // false (loopback)
 * isPublicUrl( 'http://[fd00::1]' )               ; // false (unique local)
 * isPublicUrl( 'http://[2001:4860:4860::8888]' )  ; // true
 * isPublicUrl( '/relative/path' )                 ; // false (no host)
 * isPublicUrl( '' )                               ; // false
 * ```
 *
 * @param string $url The URL whose host is inspected.
 *
 * @return bool `true` when the host is a publicly reachable FQDN or
 *              public IP, `false` for local / private / reserved
 *              hosts and host-less input.
 */
function isPublicUrl( string $url ) :bool
{
    $host = parse_url( $url , PHP_URL_HOST ) ;

    if( !is_string( $host ) || $host === '' )
    {
        return false ;
    }

    // parse_url() keeps IPv6 literals bracketed: [::1] , [2001:db8::1].
    if( $host[0] === '[' && str_ends_with( $host , ']' ) )
    {
        $host = substr( $host , 1 , -1 ) ;
    }

    $host = strtolower( $host ) ;

    if( $host === 'localhost' || str_ends_with( $host , '.localhost' ) )
    {
        return false ;
    }

    // IP literal (v4 or v6): delegate the public-vs-private/reserved decision.
    if( filter_var( $host , FILTER_VALIDATE_IP ) !== false )
    {
        return isPublicIp( $host ) ;
    }

    // Any other named host is assumed public (no DNS resolution).
    return true ;
}
