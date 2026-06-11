<?php

namespace oihana\http\helpers\ips ;

/**
 * Truncates an IPv6 address to its `/48` subnet (last 80 bits → `0`).
 *
 * The `/48` prefix is the GDPR-friendly anonymisation depth for IPv6
 * traffic, mirroring the `/24` policy applied to IPv4 by
 * {@see truncateIpToSlash24()}: coarse enough to break individual
 * traceability, fine enough to keep forensic value for an investigation.
 * The German DPA (BfDI) and the German Federal Office for Information
 * Security (BSI) explicitly recommend `/48` as the IPv6 counterpart of
 * the IPv4 `/24` policy for server logs.
 *
 * Non-IPv6 input (`null`, empty, IPv4, IPv4-mapped IPv6, or any malformed
 * shape) is returned untouched — the helper stays a no-op on shapes it
 * cannot safely truncate, matching the contract of
 * {@see truncateIpToSlash24()}.
 *
 * Examples:
 * ```php
 * echo truncateIpToSlash48( '2001:db8:cafe:1234:5678:9abc:def0:1111' ) ;
 * // 2001:db8:cafe::
 *
 * echo truncateIpToSlash48( '2001:db8:cafe::1' ) ;
 * // 2001:db8:cafe::
 *
 * echo truncateIpToSlash48( '198.51.100.42' ) ;
 * // 198.51.100.42 (IPv4 — returned as-is)
 *
 * var_dump( truncateIpToSlash48( null ) ) ;
 * // NULL (null in, null out)
 *
 * echo truncateIpToSlash48( '' ) ;
 * // '' (empty in, empty out)
 * ```
 *
 * @param string|null $ip The IPv6 address to truncate, or `null` /
 *                        empty / non-IPv6 to pass through.
 *
 * @return string|null The truncated canonical IPv6 form for a valid
 *                     IPv6 address, or the input untouched for any
 *                     other shape.
 */
function truncateIpToSlash48( ?string $ip ) :?string
{
    if ( $ip === null || $ip === '' )
    {
        return $ip ;
    }

    // Reject anything that is not a strict IPv6 address: IPv4 dotted-quads,
    // IPv4-mapped IPv6 (`::ffff:1.2.3.4` carries an IPv4 payload that the
    // /24 helper should handle), and malformed shapes flow through
    // untouched, matching the no-op contract.
    if ( filter_var( $ip , FILTER_VALIDATE_IP , FILTER_FLAG_IPV6 ) === false )
    {
        return $ip ;
    }

    // filter_var above already guaranteed a strict IPv6 address, so
    // inet_pton() always succeeds and yields exactly 16 bytes here.
    $bin = inet_pton( $ip ) ;

    // Keep the first 48 bits (6 bytes) of network prefix, zero the
    // remaining 80 bits (10 bytes) of host identifier.
    $truncated = substr( $bin , 0 , 6 ) . str_repeat( "\0" , 10 ) ;

    $result = inet_ntop( $truncated ) ;

    return $result !== false ? $result : $ip ;
}
