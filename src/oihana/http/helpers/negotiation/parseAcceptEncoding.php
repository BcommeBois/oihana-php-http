<?php

namespace oihana\http\helpers\negotiation ;

use oihana\http\enums\AcceptField ;

/**
 * Parses an `Accept-Encoding` HTTP request header (RFC 7231 §5.3.4)
 * into a list of entries sorted by q-value in descending order.
 *
 * Thin wrapper around {@see parseAcceptHeader()} — the wire syntax
 * is identical, only the conventional vocabulary differs. The
 * {@see AcceptField::TYPE} field of each returned entry carries a
 * lowercased content-coding name (e.g. `'gzip'`, `'br'`,
 * `'deflate'`, `'compress'`, `'identity'`) or the wildcard `'*'`
 * (matches any coding).
 *
 * Per RFC 7231 §5.3.4, the absence of an `Accept-Encoding` header
 * is semantically different from the empty header `Accept-Encoding:` —
 * the former means "the client accepts any encoding", the latter
 * means "the client accepts only `identity`". Detecting this
 * difference is the caller's responsibility (`getHeaderLine()`
 * returns `''` in both cases) — this parser returns `[]` for both.
 *
 * Example:
 * ```php
 * parseAcceptEncoding( 'br;q=1.0, gzip;q=0.8, *;q=0.1' ) ;
 * // [
 * //   [ type => 'br'   , quality => 1.0 , params => [] ] ,
 * //   [ type => 'gzip' , quality => 0.8 , params => [] ] ,
 * //   [ type => '*'    , quality => 0.1 , params => [] ] ,
 * // ]
 * ```
 *
 * @param string $header The raw `Accept-Encoding` header value.
 *
 * @return array<int, array{type: string, quality: float, params: array<string, string>}>
 */
function parseAcceptEncoding( string $header ) :array
{
    return parseAcceptHeader( $header ) ;
}
