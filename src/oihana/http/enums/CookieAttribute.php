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
     * The `Expires` attribute. Carries an HTTP-date value
     * (e.g. `Expires=Thu, 31 Dec 2026 23:59:59 GMT`). RFC 6265
     * recommends the IMF-fixdate format from RFC 7231.
     *
     * Pre-dates `Max-Age` (RFC 2109) and remains widely supported
     * for backwards compatibility. When both are present, `Max-Age`
     * wins per RFC 6265 §5.3.
     */
    public const string EXPIRES = 'Expires' ;

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
     * The `Partitioned` boolean flag (CHIPS — Cookies Having
     * Independent Partitioned State). When present, the cookie is
     * scoped to the top-level site of the embedding context,
     * effectively giving each top-level site its own cookie jar
     * for the same third-party cookie. Requires `Secure`.
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Cookies#partitioned_cookies
     */
    public const string PARTITIONED = 'Partitioned' ;

    /**
     * The `Path` attribute. Carries a URL path scope
     * (e.g. `Path=/`).
     */
    public const string PATH = 'Path' ;

    /**
     * The `Priority` attribute (Chromium extension to RFC 6265).
     * Carries one of the `oihana\http\enums\CookiePriority`
     * values (e.g. `Priority=Medium`). Guides cookie eviction
     * when the per-domain quota is exceeded.
     *
     * @see https://datatracker.ietf.org/doc/html/draft-west-cookie-priority-00
     */
    public const string PRIORITY = 'Priority' ;

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
