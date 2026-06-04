<?php

namespace oihana\http\helpers\url ;

/**
 * Tells whether a URL points to a local or private host — the readable
 * counterpart of {@see isPublicUrl()}.
 *
 * Returns `true` when the URL has a host **and** that host is not
 * publicly reachable: `localhost`, any `*.localhost` sub-domain, or an
 * IP literal in a loopback / private (RFC 1918 / RFC 4193) / reserved
 * range.
 *
 * This is **not** a strict negation of {@see isPublicUrl()}: a URL with
 * no host (relative path, empty string, unparseable input) is neither
 * public nor local, so both helpers return `false` for it. The presence
 * of a host is required here.
 *
 * Same syntactic heuristic as {@see isPublicUrl()}: no DNS resolution,
 * so a FQDN that resolves to a private address is still treated as
 * public (i.e. not local).
 *
 * Examples:
 * ```php
 * isLocalUrl( 'http://localhost:8080' )   ; // true
 * isLocalUrl( 'http://app.localhost' )    ; // true (sub-domain)
 * isLocalUrl( 'http://127.0.0.1' )        ; // true (loopback)
 * isLocalUrl( 'http://10.0.0.1' )         ; // true (RFC 1918)
 * isLocalUrl( 'http://[::1]' )            ; // true (loopback)
 * isLocalUrl( 'https://api.example.com' ) ; // false (public FQDN)
 * isLocalUrl( 'https://8.8.8.8' )         ; // false (public IP)
 * isLocalUrl( '/relative/path' )          ; // false (no host)
 * isLocalUrl( '' )                        ; // false (no host)
 * ```
 *
 * @param string $url The URL whose host is inspected.
 *
 * @return bool `true` when the URL has a local / private / reserved
 *              host, `false` for public hosts and host-less input.
 */
function isLocalUrl( string $url ) :bool
{
    return getHost( $url ) !== null && !isPublicUrl( $url ) ;
}
