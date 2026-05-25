<?php

namespace oihana\http\helpers\ips ;

/**
 * Returns the canonical representation of an IP address.
 *
 * This helper normalizes IPv4 and IPv6 addresses using the system
 * networking functions `inet_pton()` and `inet_ntop()`.
 *
 * Features:
 * - Normalizes IPv4 addresses.
 * - Compresses IPv6 addresses to their canonical form.
 * - Converts IPv4-mapped IPv6 addresses (`::ffff:x.x.x.x`)
 *   back to their native IPv4 representation.
 * - Returns the original value when the address cannot be parsed.
 *
 * Examples:
 * ```php
 * echo canonicalIp( '127.000.000.001' ) ;
 * // 127.0.0.1
 *
 * echo canonicalIp( '2001:0db8:0000:0000:0000:ff00:0042:8329' ) ;
 * // 2001:db8::ff00:42:8329
 *
 * echo canonicalIp( '::ffff:192.168.1.10' ) ;
 * // 192.168.1.10
 * ```
 *
 * @param string $ip The IPv4 or IPv6 address to normalize.
 *
 * @return string|null The normalized IP address.
 *                     Returns the original input if the address
 *                     is invalid or cannot be converted.
 */
function canonicalIp( string $ip ): ?string
{
    $bin = @inet_pton( $ip ) ;

    if ( $bin === false )
    {
        return $ip ; // fallback (invalid but usable)
    }

    // IPv4-mapped IPv6 (::ffff:xxx.xxx.xxx.xxx)
    $prefix = pack('H*', '00000000000000000000ffff');

    if ( str_starts_with( $bin , $prefix ) )
    {
        $bin = substr( $bin, strlen( $prefix ) ) ;
    }

    return inet_ntop( $bin ) ?: $ip ;
}
