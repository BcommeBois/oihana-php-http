<?php

namespace oihana\http\helpers\negotiation ;

use oihana\http\enums\ContentTypeField ;

/**
 * Parses a `Content-Type` HTTP header value (RFC 7231 §3.1.1.1)
 * into a structured tuple keyed by {@see ContentTypeField}
 * constants.
 *
 * Returned shape:
 * - {@see ContentTypeField::TYPE} — the `type/subtype` value
 *   (e.g. `'text/html'`), lowercased.
 * - {@see ContentTypeField::CHARSET} — the value of the
 *   `charset=` parameter (lowercased), or `null` when absent.
 * - {@see ContentTypeField::BOUNDARY} — the value of the
 *   `boundary=` parameter (case preserved), or `null` when absent.
 * - {@see ContentTypeField::PARAMS} — every parameter parsed from
 *   the header as `array<string, string>` keyed by lowercased
 *   parameter name. `charset` and `boundary` are also listed here
 *   in addition to their dedicated keys.
 *
 * Empty input returns the tuple with `type = ''` and all other
 * fields set to their empty default.
 *
 * Quoted parameter values (RFC 7230 `quoted-string`) are unwrapped
 * — the surrounding `"` are stripped before storage. The helper
 * does not decode `quoted-pair` escapes inside the value; this is
 * rarely needed in practice for `Content-Type`.
 *
 * Example:
 * ```php
 * parseContentType( 'text/html; charset=UTF-8' ) ;
 * // [
 * //   'type'     => 'text/html' ,
 * //   'charset'  => 'utf-8' ,
 * //   'boundary' => null ,
 * //   'params'   => [ 'charset' => 'utf-8' ] ,
 * // ]
 *
 * parseContentType( 'multipart/form-data; boundary="---WebKit"' ) ;
 * // [
 * //   'type'     => 'multipart/form-data' ,
 * //   'charset'  => null ,
 * //   'boundary' => '---WebKit' ,
 * //   'params'   => [ 'boundary' => '---WebKit' ] ,
 * // ]
 * ```
 *
 * @param string $header The raw `Content-Type` header value.
 *
 * @return array{type: string, charset: string|null, boundary: string|null, params: array<string, string>}
 */
function parseContentType( string $header ) :array
{
    $header = trim( $header ) ;

    if ( $header === '' )
    {
        return
        [
            ContentTypeField::TYPE     => ''   ,
            ContentTypeField::CHARSET  => null ,
            ContentTypeField::BOUNDARY => null ,
            ContentTypeField::PARAMS   => []   ,
        ] ;
    }

    $segments = array_map( 'trim' , explode( ';' , $header ) ) ;
    $type     = strtolower( array_shift( $segments ) ?? '' ) ;

    $params   = [] ;
    $charset  = null ;
    $boundary = null ;

    foreach ( $segments as $segment )
    {
        if ( $segment === '' )
        {
            continue ;
        }

        $eq = strpos( $segment , '=' ) ;

        if ( $eq === false )
        {
            // Flag-style parameter without a value (rare).
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

        if ( $name === 'charset' )
        {
            // Charset names are case-insensitive — normalise for compares.
            $charset = strtolower( $value ) ;
            $params[ $name ] = $charset ;
            continue ;
        }

        if ( $name === 'boundary' )
        {
            // Multipart boundary is case-sensitive — keep as-is.
            $boundary = $value ;
            $params[ $name ] = $value ;
            continue ;
        }

        $params[ $name ] = $value ;
    }

    return
    [
        ContentTypeField::TYPE     => $type     ,
        ContentTypeField::CHARSET  => $charset  ,
        ContentTypeField::BOUNDARY => $boundary ,
        ContentTypeField::PARAMS   => $params   ,
    ] ;
}
