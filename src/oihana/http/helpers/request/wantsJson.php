<?php

namespace oihana\http\helpers\request ;

use oihana\enums\http\HttpHeader ;
use oihana\http\enums\AcceptField ;

use Psr\Http\Message\ServerRequestInterface ;

use function oihana\http\helpers\negotiation\parseAcceptHeader ;

/**
 * Tells whether the client prefers a JSON response over any other
 * representation.
 *
 * The heuristic mirrors Laravel's `wantsJson()`: looks at the
 * **top-priority** entry of the parsed `Accept` header
 * (highest q-value, stable on header order) and returns `true`
 * when its media type contains either `/json`
 * (e.g. `application/json`, `text/json`) or `+json`
 * (e.g. `application/ld+json`, `application/vnd.api+json`).
 *
 * Returns `false` when:
 * - the `Accept` header is missing or empty ;
 * - the top entry advertises a non-JSON content type
 *   (e.g. `text/html`, `application/xml`).
 *
 * `X-Requested-With` is intentionally **not** considered here — too
 * many libraries set it for non-JSON AJAX. Use {@see isAjax()} for
 * that signal explicitly.
 *
 * Example:
 * ```php
 * // Accept: application/json, text/html;q=0.9
 * wantsJson( $request ) ; // true
 *
 * // Accept: text/html
 * wantsJson( $request ) ; // false
 *
 * // No Accept header
 * wantsJson( $request ) ; // false
 * ```
 *
 * @param ServerRequestInterface $request The PSR-7 request.
 *
 * @return bool `true` when the client's top preference is a JSON
 *              media type.
 */
function wantsJson( ServerRequestInterface $request ) :bool
{
    $entries = parseAcceptHeader( $request->getHeaderLine( HttpHeader::ACCEPT ) ) ;

    if ( $entries === [] )
    {
        return false ;
    }

    $top = $entries[ 0 ][ AcceptField::TYPE ] ;

    return str_contains( $top , '/json' )
        || str_contains( $top , '+json' ) ;
}
