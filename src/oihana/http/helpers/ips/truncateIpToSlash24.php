<?php

namespace oihana\http\helpers\ips ;

/**
 * Truncates an IPv4 address to its `/24` subnet (last octet → `0`).
 *
 * GDPR-friendly anonymisation depth shared with the bulk
 * `auth:sessions:anonymize` AQL command : coarse enough to break
 * individual traceability, fine enough to keep forensic value during
 * an incident investigation. Non-IPv4 input (`null`, empty, IPv6, or
 * anything not matching `a.b.c.d`) is returned untouched — the helper
 * stays a no-op on shapes it cannot safely truncate.
 *
 * Examples:
 * ```php
 * echo truncateIpToSlash24( '198.51.100.42' ) ;
 * // 198.51.100.0
 *
 * echo truncateIpToSlash24( '2001:db8::1' ) ;
 * // 2001:db8::1 (IPv6 — returned as-is)
 *
 * var_dump( truncateIpToSlash24( null ) ) ;
 * // NULL (null in, null out)
 *
 * echo truncateIpToSlash24( '' ) ;
 * // '' (empty in, empty out)
 * ```
 *
 * @param string|null $ip The IPv4 address to truncate, or `null` /
 *                        empty / non-IPv4 to pass through.
 *
 * @return string|null The truncated `a.b.c.0` form for valid IPv4,
 *                     or the input untouched for any other shape.
 */
function truncateIpToSlash24( ?string $ip ) :?string
{
    if ( $ip === null || $ip === '' )
    {
        return $ip ;
    }

    // `filter_var(...FILTER_FLAG_IPV4)` returns the address itself
    // when it is a valid dotted-quad IPv4, and `false` otherwise.
    // This rejects IPv6 ("2001:db8::1"), IPv4-mapped IPv6
    // ("::ffff:192.168.1.10"), and any malformed dotted shape
    // ("198.51.100" or "abc.def.ghi.jkl") — those flow through
    // untouched, matching the documented no-op contract.
    if ( filter_var( $ip , FILTER_VALIDATE_IP , FILTER_FLAG_IPV4 ) === false )
    {
        return $ip ;
    }

    $parts = explode( '.' , $ip ) ;

    return "$parts[0].$parts[1].$parts[2].0" ;
}
