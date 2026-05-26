<?php

namespace oihana\http\helpers\cookies ;

/**
 * Parses the value of an HTTP request `Cookie` header into a name-keyed
 * map of cookie values.
 *
 * Reciprocal of {@see buildSetCookieHeader()} on the read side: where the
 * builder emits a single `Set-Cookie` response header, browsers and PSR-7
 * requests carry every cookie back in a single `Cookie:` request header
 * with `name1=value1; name2=value2; …` syntax (RFC 6265 §5.4).
 *
 * Behaviour:
 * - Pairs are split on `;` and trimmed (RFC tolerates `;` with or without
 *   the trailing space).
 * - Each pair is split on the **first** `=` only — values may legitimately
 *   contain unescaped `=` characters (e.g. base64-padded tokens).
 * - Values are returned **verbatim**, without URL-decoding. Callers that
 *   wrote URL-encoded values via {@see buildSetCookieHeader()} must
 *   `urldecode()` the result themselves.
 * - Pairs without `=` are treated as flag-style entries with an empty
 *   value, matching the parsing behaviour of {@see http_parse_cookie()}.
 * - Pairs with an empty name (e.g. `=foo`) are silently dropped.
 * - When the same name appears more than once, the **last** occurrence
 *   wins, matching the behaviour of PHP's native `$_COOKIE` population.
 *
 * Examples:
 * ```php
 * parseCookieHeader( 'PHPSESSID=abc; user=jane' ) ;
 * // [ 'PHPSESSID' => 'abc' , 'user' => 'jane' ]
 *
 * parseCookieHeader( 'token=eyJhbGc=.eyJzdWI=' ) ;
 * // [ 'token' => 'eyJhbGc=.eyJzdWI=' ]
 *
 * parseCookieHeader( '' ) ;
 * // []
 * ```
 *
 * @param string $header The raw value of the `Cookie:` request header.
 *
 * @return array<string, string>
 */
function parseCookieHeader( string $header ) :array
{
    $header = trim( $header ) ;

    if ( $header === '' )
    {
        return [] ;
    }

    $result = [] ;

    foreach ( explode( ';' , $header ) as $pair )
    {
        $pair = trim( $pair ) ;

        if ( $pair === '' )
        {
            continue ;
        }

        $eq = strpos( $pair , '=' ) ;

        if ( $eq === false )
        {
            $result[ $pair ] = '' ;
            continue ;
        }

        $name  = trim( substr( $pair , 0 , $eq ) ) ;
        $value = substr( $pair , $eq + 1 ) ;

        if ( $name === '' )
        {
            continue ;
        }

        $result[ $name ] = $value ;
    }

    return $result ;
}
