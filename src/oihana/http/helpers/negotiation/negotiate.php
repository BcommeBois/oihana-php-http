<?php

namespace oihana\http\helpers\negotiation ;

use oihana\http\enums\AcceptField ;

/**
 * Selects the best server-side value from a list of `$available`
 * choices given a client `Accept*` header.
 *
 * Walks the parsed `$accept` entries (sorted by q-value descending
 * by {@see parseAcceptHeader()}) and returns the first
 * `$available` value the client accepts. Entries with `q=0`
 * explicitly refuse a value — they are skipped during the match,
 * never selected.
 *
 * Wildcard support:
 * - `'* / *'` (used in `Accept`) matches any media type.
 * - `'type/*'` matches any subtype of `type`.
 * - `'*'` (used in `Accept-Language` and `Accept-Encoding`)
 *   matches anything.
 *
 * Returns:
 * - the matched value from `$available` (cased as provided by the
 *   caller — the helper never lowercases the picked entry) when
 *   a match is found ;
 * - `$default` (or `null`) otherwise.
 *
 * Example — content type negotiation:
 * ```php
 * negotiate
 * (
 *     'text/html;q=0.9, application/json' ,
 *     [ 'application/json' , 'text/html' ] ,
 * ) ;
 * // 'application/json' (q=1.0 wins over q=0.9)
 * ```
 *
 * Example — language negotiation with wildcard:
 * ```php
 * negotiate
 * (
 *     'fr;q=0.9, *;q=0.1' ,
 *     [ 'en' , 'es' ] ,
 *     'en' ,
 * ) ;
 * // 'en' (matched via `*`, first available wins among the wildcard candidates)
 * ```
 *
 * @param string      $accept    The raw `Accept*` header value.
 * @param string[]    $available The server-side list of values, in
 *                               preference order (ties broken by
 *                               this order).
 * @param string|null $default   Returned when no `$available` value
 *                               matches.
 *
 * @return string|null The selected value, or `$default`.
 */
function negotiate( string $accept , array $available , ?string $default = null ) :?string
{
    if ( $available === [] )
    {
        return $default ;
    }

    $entries = parseAcceptHeader( $accept ) ;

    if ( $entries === [] )
    {
        return $default ;
    }

    foreach ( $entries as $entry )
    {
        if ( $entry[ AcceptField::QUALITY ] === 0.0 )
        {
            // Explicit refusal — skip without trying to match.
            continue ;
        }

        $pattern = $entry[ AcceptField::TYPE ] ;

        foreach ( $available as $candidate )
        {
            if ( matchAcceptPattern( $pattern , $candidate ) )
            {
                return $candidate ;
            }
        }
    }

    return $default ;
}

/**
 * Tests whether a candidate value satisfies an `Accept*` entry
 * pattern, applying the RFC 7231 wildcard rules.
 *
 * Internal helper. Public so callers that already have a parsed
 * entry can run a single match without re-parsing.
 *
 * @param string $pattern   The `Accept*` entry value (lowercased).
 * @param string $candidate The server-side value to test against.
 *
 * @return bool `true` when the candidate matches the pattern.
 */
function matchAcceptPattern( string $pattern , string $candidate ) :bool
{
    $candidateLower = strtolower( $candidate ) ;

    if ( $pattern === '*' || $pattern === '*/*' )
    {
        return true ;
    }

    // `type/*` wildcard for media types.
    if ( str_ends_with( $pattern , '/*' ) )
    {
        $prefix = substr( $pattern , 0 , -1 ) ; // keep the trailing `/`
        return str_starts_with( $candidateLower , $prefix ) ;
    }

    return $pattern === $candidateLower ;
}
