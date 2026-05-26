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
     * The `Expires` attribute of the cookie.
     *
     * Accepts:
     * - `int`               — a Unix timestamp (seconds since epoch) ;
     * - `DateTimeInterface` — converted to UTC and formatted as IMF-fixdate ;
     * - `string`            — passed through as-is (escape hatch for
     *                         pre-formatted dates) ;
     * - `null` or absent    — no `Expires` attribute is emitted.
     *
     * `int` and `DateTimeInterface` are formatted via
     * `org\common\DateFormat::RFC7231` on a UTC `DateTimeImmutable`,
     * producing the IMF-fixdate form recommended by RFC 6265
     * (e.g. `Thu, 31 Dec 2026 23:59:59 GMT`).
     */
    public const string EXPIRES = 'expires' ;

    /**
     * Whether to append the `HttpOnly` attribute. Default `true`.
     */
    public const string HTTP_ONLY = 'httpOnly' ;

    /**
     * Whether to append the `Partitioned` attribute (CHIPS).
     * Default `false`. Browsers require `Secure` for the
     * attribute to take effect.
     */
    public const string PARTITIONED = 'partitioned' ;

    /**
     * The `Path` attribute of the cookie. Default `/`.
     */
    public const string PATH = 'path' ;

    /**
     * The `Priority` attribute of the cookie.
     *
     * Accepts one of the `oihana\http\enums\CookiePriority`
     * constants (`LOW`, `MEDIUM`, `HIGH`). `null` or absent
     * skips the attribute entirely.
     */
    public const string PRIORITY = 'priority' ;

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
