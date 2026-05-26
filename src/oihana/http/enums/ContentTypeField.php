<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Field names of the structured tuple returned by
 * `oihana\http\helpers\negotiation\parseContentType()`.
 *
 * Centralises the array keys exposed by the parser so consumers
 * can avoid magic strings.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\negotiation\parseContentType ;
 * use oihana\http\enums\ContentTypeField ;
 *
 * $parsed = parseContentType( 'multipart/form-data; boundary=---abc' ) ;
 *
 * $parsed[ ContentTypeField::TYPE     ] ; // 'multipart/form-data'
 * $parsed[ ContentTypeField::BOUNDARY ] ; // '---abc'
 * $parsed[ ContentTypeField::CHARSET  ] ; // null
 * $parsed[ ContentTypeField::PARAMS   ] ; // [ 'boundary' => '---abc' ]
 * ```
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class ContentTypeField
{
    use ConstantsTrait ;

    /**
     * Key under which `parseContentType()` exposes the multipart
     * boundary string, when present. The value stored at this key
     * is a `string` (e.g. `'----WebKitFormBoundary7MA4YWxkTrZu0gW'`)
     * or `null` for non-multipart content types.
     */
    public const string BOUNDARY = 'boundary' ;

    /**
     * Key under which `parseContentType()` exposes the character
     * set, when present. The value stored at this key is a `string`
     * (e.g. `'utf-8'`, lowercased) or `null` when the header carries
     * no `charset=` parameter.
     */
    public const string CHARSET = 'charset' ;

    /**
     * Key under which `parseContentType()` exposes the auxiliary
     * parameters. The value stored at this key is
     * `array<string, string>` mapping lowercased parameter names
     * to their raw string values. `charset` and `boundary` are
     * also exposed here (in addition to their dedicated keys) for
     * uniform iteration.
     */
    public const string PARAMS = 'params' ;

    /**
     * Key under which `parseContentType()` exposes the media type
     * (`type/subtype`). The value stored at this key is a `string`
     * (e.g. `'text/html'`, lowercased) — matching RFC 7231 §3.1.1.1
     * case-insensitive semantics.
     */
    public const string TYPE = 'type' ;
}
