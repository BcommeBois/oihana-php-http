<?php

namespace oihana\http\helpers\ips ;

/**
 * Anonymises an IP address by routing it through the family-appropriate
 * truncation helper:
 * - valid IPv4         → {@see truncateIpToSlash24()} (`/24`, last octet zeroed)
 * - valid IPv6         → {@see truncateIpToSlash48()} (`/48`, last 80 bits zeroed)
 * - anything else      → returned untouched (no-op contract)
 *
 * IPv4-mapped IPv6 (`::ffff:1.2.3.4`) is treated as IPv6 — the helper does
 * not unmap before truncation. If you need the unmapped form, run
 * {@see canonicalIp()} first.
 *
 * Single entry point to use in logging / audit pipelines that want a
 * single anonymisation depth across both address families, matching the
 * GDPR-friendly defaults already shared with the `auth:sessions:anonymize`
 * bulk command.
 *
 * Examples:
 * ```php
 * echo anonymizeIp( '198.51.100.42' ) ;
 * // 198.51.100.0
 *
 * echo anonymizeIp( '2001:db8:cafe:1234:5678:9abc:def0:1111' ) ;
 * // 2001:db8:cafe::
 *
 * echo anonymizeIp( 'not-an-ip' ) ;
 * // not-an-ip (returned untouched)
 *
 * var_dump( anonymizeIp( null ) ) ;
 * // NULL
 * ```
 *
 * @param string|null $ip The IP address to anonymise, or `null` /
 *                        empty / non-IP to pass through.
 *
 * @return string|null The truncated address (IPv4 `/24` or IPv6 `/48`),
 *                     or the input untouched for any other shape.
 */
function anonymizeIp( ?string $ip ) :?string
{
    if ( $ip === null || $ip === '' )
    {
        return $ip ;
    }

    if ( filter_var( $ip , FILTER_VALIDATE_IP , FILTER_FLAG_IPV4 ) !== false )
    {
        return truncateIpToSlash24( $ip ) ;
    }

    if ( filter_var( $ip , FILTER_VALIDATE_IP , FILTER_FLAG_IPV6 ) !== false )
    {
        return truncateIpToSlash48( $ip ) ;
    }

    return $ip ;
}
