<?php

namespace oihana\http\helpers\ips ;

use function oihana\core\strings\unquote ;

/**
 * Parses a {@code Forwarded} HTTP header (RFC 7239) and returns the
 * ordered list of `for=` IP addresses found in it.
 *
 * The header may contain multiple comma-separated entries, each made of
 * semicolon-separated `key=value` pairs. Values may be quoted, and the
 * `for=` parameter may carry an obfuscated identifier, an IPv4 address,
 * or an IPv6 address bracketed and optionally suffixed with a port:
 *
 * ```
 * Forwarded: for=192.0.2.60;proto=http;by=203.0.113.43
 * Forwarded: for="[2001:db8:cafe::17]:4711"
 * Forwarded: for=192.0.2.43, for=198.51.100.17
 * ```
 *
 * Output: addresses are returned in header order (left-most first), with
 * surrounding quotes, brackets and trailing port stripped. Obfuscated
 * identifiers (e.g. `for=_hidden`) are skipped — only entries that look
 * like an IP address are returned. Validation of the IP itself is left
 * to the caller (use {@see filter_var()} or {@see canonicalIp()}).
 *
 * Examples:
 * ```php
 * parseForwardedHeader( 'for=192.0.2.60;proto=http' ) ;
 * // ['192.0.2.60']
 *
 * parseForwardedHeader( 'for="[2001:db8::1]:4711", for=203.0.113.43' ) ;
 * // ['2001:db8::1', '203.0.113.43']
 *
 * parseForwardedHeader( '' ) ;
 * // []
 * ```
 *
 * @param string $header The raw value of the {@code Forwarded} header.
 *
 * @return string[] Ordered list of `for=` IP candidates.
 */
function parseForwardedHeader( string $header ): array
{
    $header = trim( $header ) ;

    if ( $header === '' )
    {
        return [] ;
    }

    $result = [] ;

    foreach ( explode( ',' , $header ) as $element )
    {
        foreach ( explode( ';' , $element ) as $pair )
        {
            $pair = trim( $pair ) ;

            if ( $pair === '' )
            {
                continue ;
            }

            $eq = strpos( $pair , '=' ) ;

            if ( $eq === false )
            {
                continue ;
            }

            $key   = strtolower( trim( substr( $pair , 0 , $eq ) ) ) ;
            $value = trim( substr( $pair , $eq + 1 ) ) ;

            if ( $key !== 'for' || $value === '' )
            {
                continue ;
            }

            $value = unquote( $value ) ;

            // Bracketed IPv6, optionally with :port → [2001:db8::1]:4711
            if ( $value !== '' && $value[ 0 ] === '[' )
            {
                $close = strpos( $value , ']' ) ;

                if ( $close === false )
                {
                    continue ;
                }

                $value = substr( $value , 1 , $close - 1 ) ;
            }
            // Bare IPv4 with :port → 192.0.2.43:4711 (only one ':' → port suffix)
            else if ( substr_count( $value , ':' ) === 1 )
            {
                $value = substr( $value , 0 , strpos( $value , ':' ) ) ;
            }

            if ( $value === '' )
            {
                continue ;
            }

            // Skip obfuscated identifiers (RFC 7239 §6.3) — `for=_secret`,
            // `for=unknown`, etc. Only keep what parses as an IP.
            if ( @inet_pton( $value ) === false )
            {
                continue ;
            }

            $result[] = $value ;
        }
    }

    return $result ;
}
