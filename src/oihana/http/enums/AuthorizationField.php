<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Field names of the structured tuple returned by
 * `oihana\http\helpers\auth\parseAuthorizationHeader()`.
 *
 * Centralises the array keys so consumers can avoid magic strings.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\auth\parseAuthorizationHeader ;
 * use oihana\http\enums\AuthorizationField ;
 *
 * $parsed = parseAuthorizationHeader( 'Bearer eyJhbGci…' ) ;
 *
 * $scheme = $parsed[ AuthorizationField::SCHEME      ] ; // 'Bearer'
 * $creds  = $parsed[ AuthorizationField::CREDENTIALS ] ; // 'eyJhbGci…'
 * ```
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class AuthorizationField
{
    use ConstantsTrait ;

    /**
     * Key under which `parseAuthorizationHeader()` exposes the
     * credentials part of the header — everything after the first
     * whitespace, with leading whitespace trimmed. The value is a
     * `string`, possibly empty when the header carries a scheme
     * with no credentials (legal for some challenge responses).
     */
    public const string CREDENTIALS = 'credentials' ;

    /**
     * Key under which `parseAuthorizationHeader()` exposes the
     * authentication scheme — the first token of the
     * `Authorization` header. The value is a `string` rewritten to
     * the canonical casing carried by `oihana\enums\http\AuthScheme`
     * when the scheme is recognised (e.g. `'BEARER'` → `'Bearer'`),
     * or preserved as-is when unknown.
     */
    public const string SCHEME = 'scheme' ;
}
