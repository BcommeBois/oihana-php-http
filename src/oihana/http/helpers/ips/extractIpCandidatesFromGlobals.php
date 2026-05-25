<?php

namespace oihana\http\helpers\ips ;

use oihana\enums\ServerParam ;

/**
 * Extracts IP candidates from the native `$_SERVER` superglobal.
 *
 * Same contract as {@see extractIpCandidatesFromRequest()}, but reads
 * from `$_SERVER` instead of a PSR-7 request — used as a fallback when
 * {@see getClientIp()} is invoked without an explicit request (e.g. in
 * legacy code paths or CLI utilities running behind a web entry point).
 *
 * @param bool $useForwarded When `true`, parse the RFC 7239
 *                           {@code Forwarded} header (read from
 *                           `$_SERVER['HTTP_FORWARDED']`).
 *
 * @return array{0: ?string, 1: string[], 2: string[]}
 *         [REMOTE_ADDR, ordered header candidates, forwarded chain].
 */
function extractIpCandidatesFromGlobals( bool $useForwarded ): array
{
    $headers = [] ;
    $chain   = [] ;

    if ( $useForwarded )
    {
        $rawForwarded = $_SERVER[ 'HTTP_FORWARDED' ] ?? null ;

        if ( is_string( $rawForwarded ) && $rawForwarded !== '' )
        {
            foreach ( parseForwardedHeader( $rawForwarded ) as $ip )
            {
                $headers[] = $ip ;
                $chain[]   = $ip ;
            }
        }
    }

    $cf = $_SERVER[ ServerParam::HTTP_CF_CONNECTING_IP ] ?? null ;
    if ( is_string( $cf ) && $cf !== '' )
    {
        $headers[] = trim( $cf ) ;
    }

    $xff = $_SERVER[ ServerParam::HTTP_X_FORWARDED_FOR ] ?? null ;
    if ( is_string( $xff ) && $xff !== '' )
    {
        $parts = array_map( 'trim' , explode( ',' , $xff ) ) ;

        $headers[] = $parts[ 0 ] ;

        foreach ( $parts as $ip )
        {
            $chain[] = $ip ;
        }
    }

    $realIp = $_SERVER[ ServerParam::HTTP_X_REAL_IP ] ?? null ;
    if ( is_string( $realIp ) && $realIp !== '' )
    {
        $headers[] = trim( $realIp ) ;
    }

    $remote = $_SERVER[ ServerParam::REMOTE_ADDR ] ?? null ;
    $remote = is_string( $remote ) && $remote !== '' ? $remote : null ;

    return [ $remote , $headers , $chain ] ;
}
