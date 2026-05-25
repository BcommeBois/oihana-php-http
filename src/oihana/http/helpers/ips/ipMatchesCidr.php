<?php

namespace oihana\http\helpers\ips ;

/**
 * Tests whether an IP address matches a CIDR range (or a bare IP).
 *
 * Supports both IPv4 and IPv6 ranges. The {@code $range} argument may be
 * either:
 * - a bare IP address (treated as a `/32` for IPv4 or `/128` for IPv6) ;
 * - a CIDR notation such as `10.0.0.0/8` or `2001:db8::/32`.
 *
 * Examples:
 * ```php
 * ipMatchesCidr( '10.1.2.3'    , '10.0.0.0/8' )    ; // true
 * ipMatchesCidr( '127.0.0.1'   , '127.0.0.1' )     ; // true
 * ipMatchesCidr( '192.168.1.5' , '10.0.0.0/8' )    ; // false
 * ipMatchesCidr( '2001:db8::1' , '2001:db8::/32' ) ; // true
 * ```
 *
 * Invalid inputs (malformed IP, malformed CIDR, mismatched address
 * families) return {@code false} rather than throwing — the helper is
 * expected to fail closed when used in security-sensitive paths.
 *
 * @param string $ip    The IP address to test.
 * @param string $range A CIDR range or a bare IP address.
 *
 * @return bool True when {@code $ip} falls inside {@code $range}.
 */
function ipMatchesCidr( string $ip , string $range ): bool
{
    $ipBin = @inet_pton( $ip ) ;

    if ( $ipBin === false )
    {
        return false ;
    }

    $slash = strpos( $range , '/' ) ;

    if ( $slash === false )
    {
        $rangeIp = $range ;
        $bits    = null ;
    }
    else
    {
        $rangeIp  = substr( $range , 0 , $slash ) ;
        $rawBits  = substr( $range , $slash + 1 ) ;

        if ( $rawBits === '' || !preg_match( '/^\d+$/' , $rawBits ) )
        {
            return false ;
        }

        $bits = (int) $rawBits ;
    }

    $rangeBin = @inet_pton( $rangeIp ) ;

    if ( $rangeBin === false )
    {
        return false ;
    }

    if ( strlen( $ipBin ) !== strlen( $rangeBin ) )
    {
        return false ; // mismatched IPv4 vs IPv6
    }

    $totalBits = strlen( $ipBin ) * 8 ;

    if ( $bits === null )
    {
        $bits = $totalBits ; // bare IP → exact match
    }

    if ( $bits < 0 || $bits > $totalBits )
    {
        return false ;
    }

    if ( $bits === 0 )
    {
        return true ; // /0 matches everything in the same family
    }

    $fullBytes = intdiv( $bits , 8 ) ;
    $remainder = $bits % 8 ;

    if ( $fullBytes > 0 && substr( $ipBin , 0 , $fullBytes ) !== substr( $rangeBin , 0 , $fullBytes ) )
    {
        return false ;
    }

    if ( $remainder === 0 )
    {
        return true ;
    }

    $mask = chr( 0xFF << ( 8 - $remainder ) & 0xFF ) ;

    return ( $ipBin[ $fullBytes ] & $mask ) === ( $rangeBin[ $fullBytes ] & $mask ) ;
}
