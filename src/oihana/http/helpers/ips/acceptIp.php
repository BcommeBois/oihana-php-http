<?php

namespace oihana\http\helpers\ips ;

/**
 * Validates a candidate IP, applies the {@code $allowPrivate} filter,
 * and returns the canonical form.
 *
 * Returns {@code null} when:
 * - {@code $ip} is null or empty ;
 * - {@code $ip} is not a syntactically valid IPv4 / IPv6 address ;
 * - {@code $allowPrivate} is `false` and {@code $ip} sits in a private
 *   or reserved range ({@see isPublicIp()}).
 *
 * Otherwise returns {@see canonicalIp()} of the input.
 *
 * Used by {@see getClientIp()} to validate every candidate (header value,
 * forwarded chain entry, REMOTE_ADDR fallback) under a single set of
 * rules.
 *
 * Examples:
 * ```php
 * acceptIp( '8.8.8.8'   , true  ) ; // '8.8.8.8'
 * acceptIp( '10.0.0.1'  , true  ) ; // '10.0.0.1'
 * acceptIp( '10.0.0.1'  , false ) ; // null   (private rejected)
 * acceptIp( 'not-an-ip' , true  ) ; // null
 * acceptIp( null        , true  ) ; // null
 * ```
 *
 * @param string|null $ip           The candidate IP to validate.
 * @param bool        $allowPrivate When `false`, reject private / reserved ranges.
 *
 * @return string|null The canonical IP, or null when rejected.
 */
function acceptIp( ?string $ip , bool $allowPrivate ): ?string
{
    if ( $ip === null || $ip === '' )
    {
        return null ;
    }

    if ( !filter_var( $ip , FILTER_VALIDATE_IP ) )
    {
        return null ;
    }

    if ( !$allowPrivate && !isPublicIp( $ip ) )
    {
        return null ;
    }

    return canonicalIp( $ip ) ;
}
