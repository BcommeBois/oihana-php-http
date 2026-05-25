<?php

namespace oihana\http\helpers\ips ;

/**
 * Walks an `X-Forwarded-For` style chain right-to-left and returns the
 * first entry that is **not** a trusted proxy.
 *
 * The chain is expected in HTTP header order: the left-most entry is the
 * client (potentially spoofed), and each subsequent entry is the address
 * appended by an intermediary proxy. The standard reverse-proxy
 * resolution walks the chain right-to-left, skipping every trusted
 * intermediary, and returns the first non-trusted address — which is the
 * real client.
 *
 * Invalid (non-IP) entries are silently skipped.
 *
 * Example:
 * ```php
 * walkForwardedChain
 * (
 *     [ '8.8.8.8' , '10.0.0.99' , '10.0.0.5' ] ,
 *     [ '10.0.0.0/8' ]
 * ) ;
 * // '8.8.8.8' — both trailing 10.* are trusted, so the next entry
 * // (8.8.8.8) is treated as the real client.
 * ```
 *
 * @param string[] $chain          Chain in header order (left = client).
 * @param string[] $trustedProxies Bare IPs and / or CIDR ranges.
 *
 * @return string|null The first non-trusted IP from the right, or null
 *                     when every entry is trusted or invalid.
 */
function walkForwardedChain( array $chain , array $trustedProxies ): ?string
{
    for ( $i = count( $chain ) - 1 ; $i >= 0 ; $i-- )
    {
        $ip = $chain[ $i ] ;

        if ( !filter_var( $ip , FILTER_VALIDATE_IP ) )
        {
            continue ;
        }

        if ( !ipInList( $ip , $trustedProxies ) )
        {
            return $ip ;
        }
    }

    return null ;
}
