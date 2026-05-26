<?php

namespace oihana\http\helpers\cookies ;

use InvalidArgumentException ;

/**
 * Validates that a cookie value cannot break the `Set-Cookie` header
 * grammar nor inject HTTP control characters.
 *
 * Empty values are accepted on purpose — they are commonly used to clear
 * a cookie via {@see expireSetCookieHeader()}.
 *
 * The check rejects:
 * - ASCII control characters (`0x00`-`0x1F` and `0x7F`), which can break
 *   header framing and lead to CRLF / response-splitting attacks ;
 * - the `;` character, which would inject a new attribute into the
 *   `Set-Cookie` header.
 *
 * RFC 6265 §4.1.1 (`cookie-octet`) is stricter and also forbids whitespace,
 * `"`, `,` and `\`, but those are widely tolerated by browsers in
 * practice — they are intentionally allowed here for backwards
 * compatibility. Callers that need strict RFC 6265 compliance should
 * URL-encode their values before passing them in.
 *
 * Used internally by {@see buildSetCookieHeader()} but exposed publicly so
 * application code can validate user-supplied values defensively.
 *
 * Examples:
 * ```php
 * validateCookieValue( '' ) ;                            // ok (empty allowed)
 * validateCookieValue( 'abc123' ) ;                      // ok
 * validateCookieValue( 'foo; HttpOnly' ) ;               // throws (`;`)
 * validateCookieValue( "foo\r\nSet-Cookie: evil=1" ) ;   // throws (CRLF)
 * ```
 *
 * @param string $value The cookie value to validate (empty string allowed).
 *
 * @return void
 *
 * @throws InvalidArgumentException When the value contains a control
 *                                  character or `;`.
 */
function validateCookieValue( string $value ) :void
{
    if ( $value === '' )
    {
        return ;
    }

    if ( preg_match( '/[\x00-\x1F\x7F;]/' , $value ) )
    {
        throw new InvalidArgumentException
        (
            'Cookie value contains forbidden characters (ASCII control characters or ";").'
        ) ;
    }
}
