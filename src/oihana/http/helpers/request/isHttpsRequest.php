<?php

namespace oihana\http\helpers\request ;

use oihana\enums\http\HttpHeader ;
use oihana\enums\ServerParam ;

use Psr\Http\Message\ServerRequestInterface ;

use function oihana\http\helpers\ips\ipInList ;

/**
 * Tells whether a PSR-7 request was made over HTTPS.
 *
 * Resolution order:
 * 1. Direct scheme check via {@see UriInterface::getScheme()} —
 *    returns `true` immediately when it is `https`.
 * 2. Anti-spoofing trusted-proxy mode: when a `$trustedProxies`
 *    list is provided **and** `REMOTE_ADDR` (from the request
 *    server params) is in the list, the `X-Forwarded-Proto` header
 *    is honoured. Returns `true` when it equals `https`
 *    (case-insensitive).
 *
 * Symmetric with `oihana\http\helpers\ips\getClientIp()`:
 * forwarded headers are only trusted when the direct hop is itself
 * trusted. With an empty `$trustedProxies` list the helper ignores
 * `X-Forwarded-Proto` entirely and falls back to `false` when the
 * direct scheme is not `https`.
 *
 * Example:
 * ```php
 * // Direct HTTPS
 * isHttpsRequest( $request ) ; // true
 *
 * // Behind Cloudflare with a trusted-proxy CIDR
 * isHttpsRequest( $request , [ '173.245.48.0/20' , '...' ] ) ; // true
 *
 * // Forwarded header from an untrusted source — refused
 * isHttpsRequest( $request ) ; // false
 * ```
 *
 * @param ServerRequestInterface $request        The PSR-7 request.
 * @param string[]               $trustedProxies Bare IPs or CIDR
 *                                               ranges allowed to
 *                                               set `X-Forwarded-Proto`.
 *
 * @return bool `true` when the request is HTTPS, either directly
 *              or through a trusted proxy.
 */
function isHttpsRequest( ServerRequestInterface $request , array $trustedProxies = [] ) :bool
{
    if ( strcasecmp( $request->getUri()->getScheme() , 'https' ) === 0 )
    {
        return true ;
    }

    if ( $trustedProxies === [] )
    {
        return false ;
    }

    $serverParams = $request->getServerParams() ;
    $remoteAddr   = $serverParams[ ServerParam::REMOTE_ADDR ] ?? null ;

    if ( !is_string( $remoteAddr ) || $remoteAddr === '' )
    {
        return false ;
    }

    if ( !ipInList( $remoteAddr , $trustedProxies ) )
    {
        return false ;
    }

    $proto = strtolower
    (
        trim( $request->getHeaderLine( HttpHeader::X_FORWARDED_PROTO ) )
    ) ;

    return $proto === 'https' ;
}
