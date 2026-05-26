<?php

namespace oihana\http\helpers\negotiation ;

use oihana\http\enums\AcceptField ;

/**
 * Parses an `Accept-Language` HTTP request header (RFC 7231 §5.3.5)
 * into a list of entries sorted by q-value in descending order.
 *
 * Thin wrapper around {@see parseAcceptHeader()} — the wire syntax is
 * identical, only the conventional vocabulary differs. The
 * {@see AcceptField::TYPE} field of each returned entry carries a
 * lowercased language tag (RFC 5646 / BCP 47), e.g. `'fr-fr'`,
 * `'en'`, `'zh-hant-cn'` or `'*'` (the wildcard that matches any
 * language).
 *
 * Example:
 * ```php
 * parseAcceptLanguage( 'fr-FR, fr;q=0.9, en;q=0.8, *;q=0.5' ) ;
 * // [
 * //   [ type => 'fr-fr' , quality => 1.0 , params => [] ] ,
 * //   [ type => 'fr'    , quality => 0.9 , params => [] ] ,
 * //   [ type => 'en'    , quality => 0.8 , params => [] ] ,
 * //   [ type => '*'     , quality => 0.5 , params => [] ] ,
 * // ]
 * ```
 *
 * @param string $header The raw `Accept-Language` header value.
 *
 * @return array<int, array{type: string, quality: float, params: array<string, string>}>
 */
function parseAcceptLanguage( string $header ) :array
{
    return parseAcceptHeader( $header ) ;
}
