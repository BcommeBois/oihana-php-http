<?php

namespace oihana\http\helpers\dates ;

use DateTimeImmutable ;
use DateTimeInterface ;
use DateTimeZone ;

use org\common\DateFormat ;

/**
 * Formats a {@see DateTimeInterface} as an RFC 7231 IMF-fixdate
 * HTTP-date string, suitable for the `Date`, `Last-Modified`,
 * `Expires`, `If-Modified-Since`, `If-Unmodified-Since` and
 * `Retry-After` response/request headers.
 *
 * The input is **converted to UTC** before formatting, regardless
 * of its source timezone — every HTTP-date must end with the
 * literal token `GMT` per RFC 7231 §7.1.1.1. Producing anything
 * else is a protocol violation that breaks downstream caches.
 *
 * Uses `org\common\DateFormat::RFC7231` from `oihana/php-standards`
 * (`'D, d M Y H:i:s \G\M\T'`) as the canonical format string.
 *
 * Example:
 * ```php
 * $dt = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;
 * echo formatHttpDate( $dt ) ;
 * // Thu, 31 Dec 2026 23:59:59 GMT
 *
 * // Non-UTC inputs are converted before formatting.
 * $cest = new DateTimeImmutable( '2026-07-01 00:00:00' , new DateTimeZone( 'Europe/Paris' ) ) ;
 * echo formatHttpDate( $cest ) ;
 * // Tue, 30 Jun 2026 22:00:00 GMT
 * ```
 *
 * Symmetric with {@see parseHttpDate()} — the two are designed to
 * roundtrip.
 *
 * @param DateTimeInterface $dt The datetime to format. Mutable or
 *                              immutable instances are both
 *                              accepted; the helper never mutates
 *                              its input.
 *
 * @return string The IMF-fixdate string (always ends with `GMT`).
 */
function formatHttpDate( DateTimeInterface $dt ) :string
{
    $utc = new DateTimeZone( 'UTC' ) ;

    if ( $dt->getTimezone()->getName() !== 'UTC' )
    {
        $immutable = $dt instanceof DateTimeImmutable
            ? $dt
            : DateTimeImmutable::createFromInterface( $dt ) ;

        $dt = $immutable->setTimezone( $utc ) ;
    }

    return $dt->format( DateFormat::RFC7231 ) ;
}
