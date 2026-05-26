<?php

namespace oihana\http\helpers\cookies ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SetCookieField ;

/**
 * Parses a single `Set-Cookie` HTTP response header value into a
 * structured tuple.
 *
 * Reciprocal of {@see buildSetCookieHeader()} on the read side. Useful in
 * tests (to assert the structure of a generated header without parsing
 * raw strings) and in integration code that needs to inspect cookies set
 * by an upstream service.
 *
 * Returned shape (keys exposed by {@see SetCookieField}):
 * ```
 * [
 *     SetCookieField::NAME       => string ,
 *     SetCookieField::VALUE      => string ,
 *     SetCookieField::ATTRIBUTES => array<string, string|bool> ,
 * ]
 * ```
 *
 * Attribute keys in the `attributes` map are normalised to their canonical
 * casing via the {@see CookieAttribute} constants (e.g. `Max-Age`,
 * `HttpOnly`, `SameSite`). Unknown attributes preserve the casing of the
 * input. Boolean flag attributes (`HttpOnly`, `Secure`, `Partitioned`) map
 * to `true`. Valued attributes (`Domain`, `Path`, `Max-Age`, `Expires`,
 * `SameSite`, `Priority`) map to their raw string value.
 *
 * Behaviour:
 * - Splits on `;`, trims each segment.
 * - The **first** segment is the `name=value` pair. Pairs without `=` are
 *   treated as a name with an empty value.
 * - Subsequent segments are attributes — split on the **first** `=`. A
 *   segment without `=` is recorded as a boolean flag (`=> true`).
 * - Attribute names are matched case-insensitively against
 *   {@see CookieAttribute} constants and rewritten to canonical casing.
 *
 * Examples:
 * ```php
 * parseSetCookieHeader( 'access_token=abc; Path=/; Max-Age=3600; SameSite=Lax; HttpOnly' ) ;
 * // [
 * //     'name'       => 'access_token' ,
 * //     'value'      => 'abc' ,
 * //     'attributes' => [
 * //         'Path'     => '/' ,
 * //         'Max-Age'  => '3600' ,
 * //         'SameSite' => 'Lax' ,
 * //         'HttpOnly' => true ,
 * //     ] ,
 * // ]
 * ```
 *
 * @param string $header The raw value of a single `Set-Cookie` response header.
 *
 * @return array{name: string, value: string, attributes: array<string, string|bool>}
 */
function parseSetCookieHeader( string $header ) :array
{
    $header = trim( $header ) ;

    $segments = array_map( 'trim' , explode( ';' , $header ) ) ;

    $first = array_shift( $segments ) ?? '' ;
    $eq    = strpos( $first , '=' ) ;

    if ( $eq === false )
    {
        $name  = $first ;
        $value = '' ;
    }
    else
    {
        $name  = substr( $first , 0 , $eq ) ;
        $value = substr( $first , $eq + 1 ) ;
    }

    $canonicals = [] ;

    foreach ( CookieAttribute::enums() as $canonical )
    {
        $canonicals[ strtolower( $canonical ) ] = $canonical ;
    }

    $attributes = [] ;

    foreach ( $segments as $segment )
    {
        if ( $segment === '' )
        {
            continue ;
        }

        $eq = strpos( $segment , '=' ) ;

        if ( $eq === false )
        {
            $rawName  = $segment ;
            $attrValue = true ;
        }
        else
        {
            $rawName   = trim( substr( $segment , 0 , $eq ) ) ;
            $attrValue = substr( $segment , $eq + 1 ) ;
        }

        if ( $rawName === '' )
        {
            continue ;
        }

        $canonical = $canonicals[ strtolower( $rawName ) ] ?? $rawName ;
        $attributes[ $canonical ] = $attrValue ;
    }

    return
    [
        SetCookieField::NAME       => $name       ,
        SetCookieField::VALUE      => $value      ,
        SetCookieField::ATTRIBUTES => $attributes ,
    ] ;
}
