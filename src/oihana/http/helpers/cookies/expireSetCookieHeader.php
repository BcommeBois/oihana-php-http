<?php

namespace oihana\http\helpers\cookies ;

/**
 * Builds a `Set-Cookie` HTTP header value that expires (deletes)
 * a cookie on the client.
 *
 * Thin wrapper around `buildSetCookieHeader` with an empty value
 * and `Max-Age=0`. The `Domain`, `Path` and `SameSite` attributes
 * of the expiring header must match the original cookie or the
 * browser will keep the live cookie around.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\cookies\expireSetCookieHeader ;
 * use oihana\http\enums\CookieOption ;
 *
 * $header = expireSetCookieHeader
 * (
 *     'access_token' ,
 *     [
 *         CookieOption::DOMAIN => 'example.com' ,
 *         CookieOption::SECURE => true ,
 *     ]
 * ) ;
 * ```
 *
 * @param string $name Cookie name to expire.
 * @param array{
 *     domain?:   string ,
 *     secure?:   bool   ,
 *     path?:     string ,
 *     sameSite?: string ,
 *     httpOnly?: bool
 * } $options Optional attribute overrides keyed with
 *           `oihana\http\enums\CookieOption` constants. See
 *           `buildSetCookieHeader` for the full list.
 *
 * @return string The full `Set-Cookie` header value.
 */
function expireSetCookieHeader( string $name , array $options = [] ) :string
{
    return buildSetCookieHeader( $name , '' , 0 , $options ) ;
}
