<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Valid values for the `SameSite` cookie attribute.
 *
 * See RFC 6265bis for the specification and browser semantics:
 *
 * - `Lax`    — sent on top-level navigations (default in modern browsers).
 * - `Strict` — sent only on same-site requests.
 * - `None`   — sent on every cross-site request, requires `Secure`.
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class SameSite
{
    use ConstantsTrait ;

    /**
     * `SameSite=Lax`. Default value used by the cookie helpers.
     */
    public const string LAX = 'Lax' ;

    /**
     * `SameSite=None`. Requires the `Secure` attribute to be set.
     */
    public const string NONE = 'None' ;

    /**
     * `SameSite=Strict`. Cookie only sent on same-site requests.
     */
    public const string STRICT = 'Strict' ;
}
