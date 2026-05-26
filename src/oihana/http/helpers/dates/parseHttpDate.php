<?php

namespace oihana\http\helpers\dates ;

use DateTimeImmutable ;
use DateTimeZone ;

/**
 * Parses an HTTP-date header value (`Date`, `Last-Modified`,
 * `Expires`, `If-Modified-Since`, `If-Unmodified-Since`, …) into a
 * UTC {@see DateTimeImmutable}.
 *
 * Accepts the three legal formats listed by RFC 7231 §7.1.1.1:
 *
 * - **IMF-fixdate** (modern, recommended):
 *   `Sun, 06 Nov 1994 08:49:37 GMT`
 * - **RFC 850** (obsolete but still seen on legacy origins):
 *   `Sunday, 06-Nov-94 08:49:37 GMT`
 * - **asctime** (obsolete):
 *   `Sun Nov  6 08:49:37 1994` (single or double space before 1-digit day)
 *
 * All three formats are GMT by spec — the returned
 * {@see DateTimeImmutable} therefore always carries the UTC
 * timezone.
 *
 * Returns `null` for `null`, empty, whitespace-only or unparseable
 * input. The check is strict: a value with `UTC` instead of `GMT`,
 * a timezone offset other than `+00:00`, or any tweak to the
 * RFC-mandated layout will be rejected. Use {@see formatHttpDate()}
 * for the symmetric write side.
 *
 * Example:
 * ```php
 * $dt = parseHttpDate( 'Thu, 31 Dec 2026 23:59:59 GMT' ) ;
 * $dt instanceof DateTimeImmutable ; // true
 * $dt->getTimezone()->getName() ;    // 'UTC'
 *
 * parseHttpDate( null ) ;            // null
 * parseHttpDate( 'tomorrow' ) ;      // null (not an HTTP-date)
 * ```
 *
 * @param string|null $value The raw header value.
 *
 * @return DateTimeImmutable|null UTC datetime, or `null` when
 *                                the input is missing or invalid.
 */
function parseHttpDate( ?string $value ) :?DateTimeImmutable
{
    if ( $value === null )
    {
        return null ;
    }

    $value = trim( $value ) ;

    if ( $value === '' )
    {
        return null ;
    }

    // asctime carries `Nov  6` with double space when the day is a
    // single digit. Collapse runs of whitespace so the format
    // strings below match either form uniformly.
    $normalized = preg_replace( '/\s+/' , ' ' , $value ) ;

    $utc = new DateTimeZone( 'UTC' ) ;

    $formats =
    [
        'D, d M Y H:i:s \G\M\T' , // IMF-fixdate (RFC 7231)
        'l, d-M-y H:i:s \G\M\T' , // RFC 850
        'D M j H:i:s Y'         , // asctime
    ] ;

    foreach ( $formats as $format )
    {
        // The leading `!` resets unspecified components to the epoch
        // so the result is deterministic.
        $dt = DateTimeImmutable::createFromFormat( '!' . $format , $normalized , $utc ) ;

        if ( $dt !== false )
        {
            return $dt ;
        }
    }

    return null ;
}
