<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Field names of the entries returned by the `Accept*`-header
 * parsers under `oihana\http\helpers\negotiation` :
 * {@see \oihana\http\helpers\negotiation\parseAcceptHeader()},
 * {@see \oihana\http\helpers\negotiation\parseAcceptLanguage()} and
 * {@see \oihana\http\helpers\negotiation\parseAcceptEncoding()}.
 *
 * Centralises the array keys so consumers can avoid magic strings.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\negotiation\parseAcceptHeader ;
 * use oihana\http\enums\AcceptField ;
 *
 * foreach ( parseAcceptHeader( 'text/html;q=0.9, application/json' ) as $entry )
 * {
 *     $type    = $entry[ AcceptField::TYPE    ] ; // 'text/html'
 *     $quality = $entry[ AcceptField::QUALITY ] ; // 0.9
 *     $params  = $entry[ AcceptField::PARAMS  ] ; // [ ... ]
 * }
 * ```
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class AcceptField
{
    use ConstantsTrait ;

    /**
     * Key under which the negotiation parsers expose the auxiliary
     * parameters that came alongside the entry (everything except
     * the `q` quality factor, which has its own key
     * {@see QUALITY}). Lowercased parameter names mapped to their
     * raw string values.
     */
    public const string PARAMS = 'params' ;

    /**
     * Key under which the negotiation parsers expose the q-value
     * (`quality`) of the entry. Always a `float` in the closed
     * range `[0.0, 1.0]`. Defaults to `1.0` when the `q=`
     * parameter is absent or unparseable. Entries are returned
     * sorted by this value in descending order.
     */
    public const string QUALITY = 'quality' ;

    /**
     * Key under which the negotiation parsers expose the negotiated
     * value itself — the media type for `Accept`
     * (e.g. `'text/html'`, `'application/json'`, `'*\/*'`), the
     * language tag for `Accept-Language` (e.g. `'fr-fr'`, `'en'`,
     * `'*'`) or the encoding name for `Accept-Encoding`
     * (e.g. `'gzip'`, `'br'`, `'identity'`).
     *
     * Lowercased for case-insensitive matching, matching RFC 7231
     * §3.1.1.1 (media types) and RFC 4647 §3.3.1 (language tags).
     */
    public const string TYPE = 'type' ;
}
