<?php

namespace oihana\http\helpers\cookies ;

use InvalidArgumentException ;

/**
 * Validates that a cookie name conforms to the RFC 6265 / RFC 7230 `token`
 * grammar.
 *
 * Per RFC 6265 §4.1.1, the `cookie-name` of a `Set-Cookie` header must be a
 * RFC 7230 `token`: a non-empty sequence of ASCII characters limited to the
 * digits, letters, and the symbols `! # $ % & ' * + - . ^ _ ` | ~`.
 * Whitespace, separators (`( ) < > @ , ; : \ " / [ ] ? = { }`) and any
 * control character are forbidden.
 *
 * Used internally by {@see buildSetCookieHeader()} but exposed publicly so
 * application code can validate user-supplied names defensively.
 *
 * Examples:
 * ```php
 * validateCookieName( 'access_token' ) ; // ok
 * validateCookieName( 'foo bar' ) ;      // throws (space)
 * validateCookieName( 'foo;bar' ) ;      // throws (separator)
 * validateCookieName( '' ) ;             // throws (empty)
 * ```
 *
 * @param string $name The cookie name to validate.
 *
 * @return void
 *
 * @throws InvalidArgumentException When the name is empty or contains any
 *                                  character outside the RFC 7230 `token`
 *                                  grammar.
 */
function validateCookieName( string $name ) :void
{
    if ( $name === '' )
    {
        throw new InvalidArgumentException( 'Cookie name must not be empty.' ) ;
    }

    if ( !preg_match( '/^[A-Za-z0-9!#$%&\'*+.^_`|~\-]+$/' , $name ) )
    {
        throw new InvalidArgumentException
        (
            sprintf
            (
                'Cookie name "%s" contains characters outside the RFC 7230 token grammar.' ,
                $name ,
            )
        ) ;
    }
}
