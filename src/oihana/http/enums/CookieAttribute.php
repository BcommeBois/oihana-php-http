<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * RFC 6265 cookie attribute names, as emitted in a `Set-Cookie`
 * HTTP response header.
 *
 * Attribute names are emitted verbatim (case as defined by the
 * RFC). Some attributes carry a value (`Path=...`, `Max-Age=...`,
 * `Domain=...`, `SameSite=...`) while others are boolean flags
 * (`HttpOnly`, `Secure`).
 *
 * Used internally by the cookie helpers
 * (`oihana\http\helpers\cookies\buildSetCookieHeader` and
 * `oihana\http\helpers\cookies\expireSetCookieHeader`) to avoid
 * sprinkling protocol literals across the codebase.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6265
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class CookieAttribute
{
    use ConstantsTrait ;

    /**
     * The `Domain` attribute. Carries a host name value
     * (e.g. `Domain=example.com`).
     */
    public const string DOMAIN = 'Domain' ;

    /**
     * The `HttpOnly` boolean flag. When present, the cookie is
     * inaccessible to JavaScript via `document.cookie`.
     */
    public const string HTTP_ONLY = 'HttpOnly' ;

    /**
     * The `Max-Age` attribute. Carries a TTL in seconds
     * (e.g. `Max-Age=3600`). A value of `0` expires the cookie.
     */
    public const string MAX_AGE = 'Max-Age' ;

    /**
     * The `Path` attribute. Carries a URL path scope
     * (e.g. `Path=/`).
     */
    public const string PATH = 'Path' ;

    /**
     * The `SameSite` attribute. Carries one of the
     * `oihana\http\enums\SameSite` values
     * (e.g. `SameSite=Lax`).
     */
    public const string SAME_SITE = 'SameSite' ;

    /**
     * The `Secure` boolean flag. When present, the cookie is
     * only sent over HTTPS.
     */
    public const string SECURE = 'Secure' ;
}
