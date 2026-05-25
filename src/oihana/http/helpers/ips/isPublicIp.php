<?php

namespace oihana\http\helpers\ips ;

/**
 * Tells whether an IP address is publicly routable.
 *
 * Returns {@code true} when {@code $ip} is a syntactically valid IPv4 or
 * IPv6 address that is **neither** in a private range (RFC 1918, RFC 4193)
 * **nor** in a reserved range (loopback, link-local, multicast, etc.).
 *
 * Internally relies on PHP's `filter_var()` with the
 * `FILTER_FLAG_NO_PRIV_RANGE` and `FILTER_FLAG_NO_RES_RANGE` flags.
 *
 * Examples:
 * ```php
 * isPublicIp( '8.8.8.8' )         ; // true
 * isPublicIp( '10.0.0.1' )        ; // false (private)
 * isPublicIp( '127.0.0.1' )       ; // false (reserved / loopback)
 * isPublicIp( '169.254.0.1' )     ; // false (link-local)
 * isPublicIp( '::1' )             ; // false (loopback)
 * isPublicIp( 'not-an-ip' )       ; // false
 * ```
 *
 * @param string $ip The IP address to test.
 *
 * @return bool True when the address is a valid public IP.
 */
function isPublicIp( string $ip ): bool
{
    return (bool) filter_var
    (
        $ip ,
        FILTER_VALIDATE_IP ,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) ;
}
