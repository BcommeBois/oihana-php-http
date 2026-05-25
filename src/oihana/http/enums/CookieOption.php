<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Option keys accepted by the cookie helpers
 * (`oihana\http\helpers\cookies\buildSetCookieHeader` and
 * `oihana\http\helpers\cookies\expireSetCookieHeader`).
 *
 * Used to type the associative `$options` array passed to the
 * helpers and avoid magic strings at call sites.
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class CookieOption
{
    use ConstantsTrait ;

    /**
     * The `Domain` attribute of the cookie.
     *
     * Empty string skips the attribute entirely.
     */
    public const string DOMAIN = 'domain' ;

    /**
     * Whether to append the `HttpOnly` attribute. Default `true`.
     */
    public const string HTTP_ONLY = 'httpOnly' ;

    /**
     * The `Path` attribute of the cookie. Default `/`.
     */
    public const string PATH = 'path' ;

    /**
     * The `SameSite` attribute of the cookie.
     *
     * Default `Lax`. Use the `oihana\http\enums\SameSite` constants
     * for valid values.
     */
    public const string SAME_SITE = 'sameSite' ;

    /**
     * Whether to append the `Secure` attribute (HTTPS only).
     * Default `false`.
     */
    public const string SECURE = 'secure' ;
}
