<?php

namespace oihana\http\helpers\ips ;

/**
 * Tells whether {@code $ip} matches **any** entry in a list of IPs / CIDRs.
 *
 * Each entry of {@code $list} is delegated to {@see ipMatchesCidr()}, so it
 * may be either a bare IP address (treated as a `/32` for IPv4 or `/128`
 * for IPv6) or a CIDR notation such as `10.0.0.0/8`.
 *
 * Useful for trusted-proxy filtering and IP whitelists.
 *
 * Examples:
 * ```php
 * ipInList( '10.1.2.3'  , [ '127.0.0.1' , '10.0.0.0/8' ] )    ; // true
 * ipInList( '8.8.8.8'   , [ '127.0.0.1' , '10.0.0.0/8' ] )    ; // false
 * ipInList( '2001:db8::1' , [ '2001:db8::/32' ] )             ; // true
 * ```
 *
 * @param string   $ip   The IP address to test.
 * @param string[] $list Bare IPs and / or CIDR ranges.
 *
 * @return bool True when {@code $ip} matches at least one entry.
 */
function ipInList( string $ip , array $list ): bool
{
    foreach ( $list as $range )
    {
        if ( ipMatchesCidr( $ip , $range ) )
        {
            return true ;
        }
    }

    return false ;
}
