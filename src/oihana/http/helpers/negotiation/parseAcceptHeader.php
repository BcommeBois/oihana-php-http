<?php

namespace oihana\http\helpers\negotiation ;

use oihana\http\enums\AcceptField ;

/**
 * Parses an `Accept`-style HTTP request header (RFC 7231 §5.3) into
 * a list of entries sorted by q-value in descending order.
 *
 * Works for any `Accept*` header that follows the same syntax —
 * dedicated wrappers {@see parseAcceptLanguage()} and
 * {@see parseAcceptEncoding()} delegate to this helper for the
 * `Accept-Language` and `Accept-Encoding` variants.
 *
 * Each returned entry is keyed with {@see AcceptField} constants:
 * - {@see AcceptField::TYPE} — the negotiated value
 *   (`'text/html'`, `'fr-fr'`, `'gzip'` …), lowercased.
 * - {@see AcceptField::QUALITY} — the `q` parameter as a `float`
 *   in `[0.0, 1.0]`. Defaults to `1.0` when absent or
 *   unparseable.
 * - {@see AcceptField::PARAMS} — all other parameters as an
 *   `array<string, string>` keyed by lowercased parameter name
 *   (e.g. `['level' => '1']`).
 *
 * Sorting:
 * - Primary key: q-value descending.
 * - Secondary key: original header order (stable sort) so that
 *   `text/html, application/json` keeps `text/html` first when
 *   both are quality `1.0`.
 *
 * Empty entries and entries with `q=0` are kept in the output but
 * land last (they explicitly refuse the value, callers may want
 * to detect them).
 *
 * Example:
 * ```php
 * parseAcceptHeader( 'text/html;q=0.9, application/json, * / *;q=0.1' ) ;
 * // [
 * //   [ type => 'application/json' , quality => 1.0 , params => [] ] ,
 * //   [ type => 'text/html'        , quality => 0.9 , params => [] ] ,
 * //   [ type => '* / *'            , quality => 0.1 , params => [] ] ,
 * // ]
 * ```
 *
 * @param string $header The raw `Accept*` header value.
 *
 * @return array<int, array{type: string, quality: float, params: array<string, string>}>
 */
function parseAcceptHeader( string $header ) :array
{
    $header = trim( $header ) ;

    if ( $header === '' )
    {
        return [] ;
    }

    $entries = [] ;
    $order   = 0 ;

    foreach ( explode( ',' , $header ) as $rawEntry )
    {
        $rawEntry = trim( $rawEntry ) ;

        if ( $rawEntry === '' )
        {
            continue ;
        }

        $segments = array_map( 'trim' , explode( ';' , $rawEntry ) ) ;

        $type = strtolower( array_shift( $segments ) ?? '' ) ;

        if ( $type === '' )
        {
            continue ;
        }

        $quality = 1.0 ;
        $params  = [] ;

        foreach ( $segments as $segment )
        {
            if ( $segment === '' )
            {
                continue ;
            }

            $eq = strpos( $segment , '=' ) ;

            if ( $eq === false )
            {
                $params[ strtolower( $segment ) ] = '' ;
                continue ;
            }

            $name  = strtolower( trim( substr( $segment , 0 , $eq ) ) ) ;
            $value = trim( substr( $segment , $eq + 1 ) ) ;

            if ( $name === '' )
            {
                continue ;
            }

            // Strip surrounding quotes from RFC 7230 quoted-string values.
            if ( strlen( $value ) >= 2 && $value[ 0 ] === '"' && $value[ -1 ] === '"' )
            {
                $value = substr( $value , 1 , -1 ) ;
            }

            if ( $name === 'q' )
            {
                if ( is_numeric( $value ) )
                {
                    $q = (float) $value ;
                    if ( $q < 0.0 ) { $q = 0.0 ; }
                    if ( $q > 1.0 ) { $q = 1.0 ; }
                    $quality = $q ;
                }
                continue ;
            }

            $params[ $name ] = $value ;
        }

        $entries[] =
        [
            'order' => $order++ ,
            AcceptField::TYPE    => $type    ,
            AcceptField::QUALITY => $quality ,
            AcceptField::PARAMS  => $params  ,
        ] ;
    }

    // Sort by q DESC, then by original order ASC (stable).
    usort
    (
        $entries ,
        fn( array $a , array $b ) :int =>
            $b[ AcceptField::QUALITY ] <=> $a[ AcceptField::QUALITY ]
            ?: $a[ 'order' ] <=> $b[ 'order' ] ,
    ) ;

    return array_map
    (
        fn( array $entry ) :array =>
        [
            AcceptField::TYPE    => $entry[ AcceptField::TYPE    ] ,
            AcceptField::QUALITY => $entry[ AcceptField::QUALITY ] ,
            AcceptField::PARAMS  => $entry[ AcceptField::PARAMS  ] ,
        ] ,
        $entries ,
    ) ;
}
