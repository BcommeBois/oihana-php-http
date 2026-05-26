<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Valid values for the `Priority` cookie attribute.
 *
 * The `Priority` attribute is a Chromium extension to RFC 6265
 * (also adopted by other major browsers) that guides cookie
 * eviction when the per-domain quota is exceeded. Higher-priority
 * cookies are evicted last.
 *
 * - `Low`    — evicted first when the quota fills up.
 * - `Medium` — default eviction policy when the attribute is absent.
 * - `High`   — evicted last; reserve for critical session cookies.
 *
 * @see https://datatracker.ietf.org/doc/html/draft-west-cookie-priority-00
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class CookiePriority
{
    use ConstantsTrait ;

    /**
     * `Priority=Low`. Cookies with this priority are evicted first
     * when the per-domain quota is exceeded.
     */
    public const string LOW = 'Low' ;

    /**
     * `Priority=Medium`. Default eviction policy applied when no
     * `Priority` attribute is set.
     */
    public const string MEDIUM = 'Medium' ;

    /**
     * `Priority=High`. Cookies with this priority are evicted last.
     */
    public const string HIGH = 'High' ;
}
