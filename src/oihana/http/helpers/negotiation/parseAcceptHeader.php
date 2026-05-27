<?php

namespace oihana\http\helpers\negotiation ;

use oihana\http\enums\AcceptField ;

use function oihana\core\strings\parseParameters ;
use function oihana\core\strings\splitOutsideQuotes ;

/**
 * Parses an `Accept`-style HTTP request header (RFC 7231 §5.3) into
 * a list of entries sorted by q-value in descending order.
 *
 * Works for **any** header that follows the RFC 7231 §5.3
 * `Accept*` grammar — call it directly for `Accept`,
 * `Accept-Language` (RFC 4647 — language tags are lowercased,
 * matching RFC 4647 §3.3.1 case-insensitive comparison) and
 * `Accept-Encoding` (RFC 7231 §5.3.4). The returned structure is
 * identical for the three; the conventional vocabulary just
 * differs in what the `type` field carries (a media type, a
 * language tag, or an encoding name).
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

    // Split on `,` while respecting quoted regions — defensive against
    // pathological cases like `text/html;q="0,1", application/json`.
    foreach ( splitOutsideQuotes( $header , ',' , true ) as $rawEntry )
    {
        if ( $rawEntry === '' )
        {
            continue ;
        }

        // Split type from params on the first `;` outside of quotes.
        $semi = strpos( $rawEntry , ';' ) ;

        if ( $semi === false )
        {
            $type   = strtolower( $rawEntry ) ;
            $params = [] ;
        }
        else
        {
            $type   = strtolower( trim( substr( $rawEntry , 0 , $semi ) ) ) ;
            $params = parseParameters( substr( $rawEntry , $semi + 1 ) , ';' , '=' , true ) ;
        }

        if ( $type === '' )
        {
            continue ;
        }

        // `q` is the special quality parameter; lift it out of the
        // generic params map after parsing.
        $quality = 1.0 ;
        if ( isset( $params[ 'q' ] ) )
        {
            if ( is_numeric( $params[ 'q' ] ) )
            {
                $quality = max( 0.0 , min( 1.0 , (float) $params[ 'q' ] ) ) ;
            }
            unset( $params[ 'q' ] ) ;
        }

        $entries[] =
        [
            AcceptField::TYPE    => $type    ,
            AcceptField::QUALITY => $quality ,
            AcceptField::PARAMS  => $params  ,
        ] ;
    }

    // Sort by q DESC. PHP >= 8.0 guarantees `usort` is stable, so
    // entries with the same q-value naturally keep their insertion
    // (i.e. header) order — no sentinel index needed.
    usort
    (
        $entries ,
        fn( array $a , array $b ) :int => $b[ AcceptField::QUALITY ] <=> $a[ AcceptField::QUALITY ] ,
    ) ;

    return $entries ;
}
