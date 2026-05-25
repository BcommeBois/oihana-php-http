<?php

namespace oihana\http\helpers\cookies ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\CookieOption ;
use oihana\http\enums\SameSite ;

/**
 * Builds a `Set-Cookie` HTTP header value.
 *
 * Reasonable defaults are applied so the helper is safe to use
 * for session/auth cookies without specifying every attribute:
 *
 * - `Path=/`
 * - `HttpOnly`
 * - `SameSite=Lax`
 * - no `Domain` attribute
 * - no `Secure` attribute
 *
 * Override any default via the `$options` array, keyed with
 * `oihana\http\enums\CookieOption` constants.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\cookies\buildSetCookieHeader ;
 * use oihana\http\enums\CookieOption ;
 *
 * $header = buildSetCookieHeader
 * (
 *     'access_token' ,
 *     $token ,
 *     3600 ,
 *     [
 *         CookieOption::DOMAIN => 'example.com' ,
 *         CookieOption::SECURE => true ,
 *     ]
 * ) ;
 * ```
 *
 * @param string      $name    Cookie name.
 * @param string|null $value   Cookie value. `null` is rendered as
 *                             an empty value (suitable for
 *                             clearing cookies — see also
 *                             `expireSetCookieHeader`).
 * @param int         $maxAge  Cookie lifetime in seconds. Use `0`
 *                             to expire the cookie immediately.
 * @param array{
 *     domain?:   string ,
 *     secure?:   bool   ,
 *     path?:     string ,
 *     sameSite?: string ,
 *     httpOnly?: bool
 * } $options Optional attribute overrides keyed with
 *           `CookieOption` constants:
 *           - `CookieOption::DOMAIN`    (string, default `''`)
 *           - `CookieOption::SECURE`    (bool, default `false`)
 *           - `CookieOption::PATH`      (string, default `'/'`)
 *           - `CookieOption::SAME_SITE` (string, default `SameSite::LAX`)
 *           - `CookieOption::HTTP_ONLY` (bool, default `true`)
 *
 * @return string The full `Set-Cookie` header value.
 */
function buildSetCookieHeader( string $name , ?string $value , int $maxAge , array $options = [] ) :string
{
    $domain   = $options[ CookieOption::DOMAIN    ] ?? ''             ;
    $secure   = $options[ CookieOption::SECURE    ] ?? false          ;
    $path     = $options[ CookieOption::PATH      ] ?? '/'            ;
    $sameSite = $options[ CookieOption::SAME_SITE ] ?? SameSite::LAX  ;
    $httpOnly = $options[ CookieOption::HTTP_ONLY ] ?? true           ;

    $parts =
    [
        "$name=$value" ,
        CookieAttribute::PATH      . "=$path"     ,
        CookieAttribute::MAX_AGE   . "=$maxAge"   ,
        CookieAttribute::SAME_SITE . "=$sameSite" ,
    ] ;

    if( $httpOnly )
    {
        $parts[] = CookieAttribute::HTTP_ONLY ;
    }

    if( $secure )
    {
        $parts[] = CookieAttribute::SECURE ;
    }

    if( $domain )
    {
        $parts[] = CookieAttribute::DOMAIN . "=$domain" ;
    }

    return implode( '; ' , $parts ) ;
}
