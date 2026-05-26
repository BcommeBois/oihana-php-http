<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Field names of the structured tuple returned by
 * `oihana\http\helpers\cookies\parseSetCookieHeader()`.
 *
 * Centralises the array keys exposed by the parser so consumers do not
 * have to sprinkle magic strings (`'name'`, `'value'`, `'attributes'`)
 * across their code.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\cookies\parseSetCookieHeader ;
 * use oihana\http\enums\SetCookieField ;
 *
 * $parsed = parseSetCookieHeader( $header ) ;
 *
 * $name  = $parsed[ SetCookieField::NAME       ] ;
 * $value = $parsed[ SetCookieField::VALUE      ] ;
 * $attrs = $parsed[ SetCookieField::ATTRIBUTES ] ;
 * ```
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class SetCookieField
{
    use ConstantsTrait ;

    /**
     * Key under which `parseSetCookieHeader()` exposes the attribute map.
     *
     * The value stored at this key is `array<string, string|bool>` —
     * attribute names (normalised to canonical casing via
     * `oihana\http\enums\CookieAttribute` when known) mapped to either
     * the raw attribute value (string) or `true` for boolean flag
     * attributes (`HttpOnly`, `Secure`, `Partitioned`).
     */
    public const string ATTRIBUTES = 'attributes' ;

    /**
     * The cookie name (the part before the first `=` in the header).
     */
    public const string NAME = 'name' ;

    /**
     * The cookie value (the part after the first `=` in the header).
     */
    public const string VALUE = 'value' ;
}
