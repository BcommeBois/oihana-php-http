<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Field names of the structured tuple returned by
 * `oihana\http\helpers\auth\getBasicAuth()`.
 *
 * Centralises the array keys so consumers can avoid magic strings.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\auth\getBasicAuth ;
 * use oihana\http\enums\BasicAuthField ;
 *
 * $creds = getBasicAuth( $request ) ;
 *
 * if ( $creds !== null )
 * {
 *     $user = $creds[ BasicAuthField::USER ] ;
 *     $pass = $creds[ BasicAuthField::PASS ] ;
 * }
 * ```
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class BasicAuthField
{
    use ConstantsTrait ;

    /**
     * Key under which `getBasicAuth()` exposes the password — the
     * part of the decoded `user-id ":" password` payload after the
     * **first** colon. The value is a `string`, possibly empty when
     * the user supplied a username without a password.
     */
    public const string PASS = 'pass' ;

    /**
     * Key under which `getBasicAuth()` exposes the username — the
     * part of the decoded `user-id ":" password` payload before the
     * first colon. The value is a `string`, possibly empty.
     */
    public const string USER = 'user' ;
}
