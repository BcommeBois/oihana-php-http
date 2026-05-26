<?php

namespace oihana\http\helpers\request ;

use Psr\Http\Message\ServerRequestInterface ;

/**
 * Tells whether a PSR-7 request was made via the legacy
 * `XMLHttpRequest` JavaScript API.
 *
 * Returns `true` when the request carries the de-facto-standard
 * `X-Requested-With: XMLHttpRequest` header (case-insensitive).
 *
 * Caveats:
 * - jQuery, Axios in legacy mode and most older AJAX libraries
 *   set this header automatically — the check is reliable for
 *   those.
 * - `fetch()` does **not** set it automatically; the caller must
 *   add it explicitly. Modern SPAs may therefore look "non-AJAX"
 *   to this helper even when they are.
 * - Distinct from {@see wantsJson()} — being AJAX does not imply
 *   wanting a JSON response (fragment HTML over AJAX is common).
 *
 * @param ServerRequestInterface $request The PSR-7 request.
 *
 * @return bool `true` when the `X-Requested-With` header equals
 *              `XMLHttpRequest` (case-insensitive).
 */
function isAjax( ServerRequestInterface $request ) :bool
{
    return strcasecmp
    (
        $request->getHeaderLine( 'X-Requested-With' ) ,
        'XMLHttpRequest' ,
    ) === 0 ;
}
