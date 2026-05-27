<?php

namespace oihana\http\helpers\negotiation ;

use oihana\http\enums\ContentTypeField ;

use function oihana\core\strings\parseParameters ;

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

    // Split type from params on the first `;`.
    $semi = strpos( $header , ';' ) ;

    if ( $semi === false )
    {
        $type   = strtolower( trim( $header ) ) ;
        $params = [] ;
    }
    else
    {
        $type   = strtolower( trim( substr( $header , 0 , $semi ) ) ) ;
        $params = parseParameters( substr( $header , $semi + 1 ) , ';' , '=' , true ) ;
    }

    // `charset` is case-insensitive per RFC — lowercase it in-place for both the dedicated key and the params map.
    $charset = null ;
    if ( isset( $params[ 'charset' ] ) )
    {
        $charset             = strtolower( $params[ 'charset' ] ) ;
        $params[ 'charset' ] = $charset ;
    }

    // `boundary` is case-sensitive per RFC 2046 — keep as-is.
    $boundary = $params[ 'boundary' ] ?? null ;

    return
    [
        ContentTypeField::TYPE     => $type     ,
        ContentTypeField::CHARSET  => $charset  ,
        ContentTypeField::BOUNDARY => $boundary ,
        ContentTypeField::PARAMS   => $params   ,
    ] ;
}
