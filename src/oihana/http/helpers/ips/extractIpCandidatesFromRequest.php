<?php

namespace oihana\http\helpers\ips ;

use oihana\enums\ServerParam ;
use oihana\enums\http\HttpHeader ;

use Psr\Http\Message\ServerRequestInterface ;

/**
 * Extracts IP candidates from a PSR-7 request.
 *
 * Reads, in priority order, every header that may carry the original
 * client IP and returns three lists:
 * 1. The direct hop ({@code REMOTE_ADDR} from the request server params),
 *    or null when missing.
 * 2. The ordered list of header candidates (CF-Connecting-IP, the first
 *    entry of X-Forwarded-For, and X-Real-IP), optionally prefixed with
 *    the addresses parsed from the {@code Forwarded} header (RFC 7239)
 *    when {@code $useForwarded} is `true`.
 * 3. The forwarded chain in HTTP order (left = client) — a concatenation
 *    of every X-Forwarded-For entry and every {@code Forwarded} `for=`
 *    address. Used by {@see walkForwardedChain()} when a trusted-proxy
 *    list is provided.
 *
 * Used by {@see getClientIp()}; not expected to be called directly in
 * application code.
 *
 * @param ServerRequestInterface $request      The PSR-7 request to inspect.
 * @param bool                   $useForwarded When `true`, parse the RFC 7239
 *                                             {@code Forwarded} header.
 *
 * @return array{0: ?string, 1: string[], 2: string[]}
 *         [REMOTE_ADDR, ordered header candidates, forwarded chain].
 */
function extractIpCandidatesFromRequest( ServerRequestInterface $request , bool $useForwarded ): array
{
    $headers = [] ;
    $chain   = [] ;

    if ( $useForwarded )
    {
        foreach ( parseForwardedHeader( $request->getHeaderLine( HttpHeader::FORWARDED ) ) as $ip )
        {
            $headers[] = $ip ;
            $chain[]   = $ip ;
        }
    }

    $cf = $request->getHeaderLine( HttpHeader::CF_CONNECTING_IP ) ;
    if ( $cf !== '' )
    {
        $headers[] = trim( $cf ) ;
    }

    $forwarded = $request->getHeaderLine( HttpHeader::X_FORWARDED_FOR ) ;
    if ( $forwarded !== '' )
    {
        $parts = array_map( 'trim' , explode( ',' , $forwarded ) ) ;

        $headers[] = $parts[ 0 ] ;

        foreach ( $parts as $ip )
        {
            $chain[] = $ip ;
        }
    }

    $realIp = $request->getHeaderLine( HttpHeader::X_REAL_IP ) ;
    if ( $realIp !== '' )
    {
        $headers[] = trim( $realIp ) ;
    }

    $remote = $request->getServerParams()[ ServerParam::REMOTE_ADDR ] ?? null ;
    $remote = is_string( $remote ) && $remote !== '' ? $remote : null ;

    return [ $remote , $headers , $chain ] ;
}
