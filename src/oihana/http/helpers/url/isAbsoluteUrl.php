<?php

namespace oihana\http\helpers\url ;

/**
 * Tells whether a string is an absolute URL (i.e. carries a
 * scheme component).
 *
 * Per RFC 3986 §4.3, an URI reference is absolute when it starts
 * with a `scheme:` token where the scheme is `ALPHA *( ALPHA /
 * DIGIT / "+" / "-" / "." )`. This helper uses a strict regex
 * matching that grammar — no DNS lookup, no protocol validation.
 *
 * Returns:
 * - `true` for `https://example.com/path`,
 *   `mailto:alice@example.com`, `data:text/plain;base64,...`,
 *   `file:///etc/passwd`, …
 * - `false` for `//example.com/path` (protocol-relative — has
 *   authority but no scheme), `/absolute/path` (path-absolute,
 *   no scheme), `relative/path`, the empty string, …
 *
 * Examples:
 * ```php
 * isAbsoluteUrl( 'https://example.com/path' ) ; // true
 * isAbsoluteUrl( 'mailto:alice@example.com' ) ; // true
 * isAbsoluteUrl( '//example.com/path' ) ;       // false (no scheme)
 * isAbsoluteUrl( '/api/v1' ) ;                  // false
 * isAbsoluteUrl( 'api/v1' ) ;                   // false
 * isAbsoluteUrl( '' ) ;                         // false
 * ```
 *
 * @param string $url The URL string to inspect.
 *
 * @return bool `true` when the input carries a scheme component.
 */
function isAbsoluteUrl( string $url ) :bool
{
    return preg_match( '/^[A-Za-z][A-Za-z0-9+.\-]*:/' , $url ) === 1 ;
}
