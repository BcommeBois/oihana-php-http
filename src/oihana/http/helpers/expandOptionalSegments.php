<?php

declare( strict_types = 1 );

namespace oihana\http\helpers;

/**
 * Expands a Slim route pattern carrying optional bracket segments
 * into its concrete variants.
 *
 * Slim allows optional segments via square brackets at the top level
 * of a route pattern, e.g. `/users[/{id:[0-9]+}]` registers a single
 * route that serves both `/users` and `/users/{id:[0-9]+}` with the
 * same callable. Consumers that need a 1:1 mapping between a route
 * row and a seeded permission (`AuthRoutesDumpCommand`,
 * `AuthTestServiceProbeCommand`, …) must expand each optional segment
 * into its concrete variants first.
 *
 * Brackets nested inside a `{...}` placeholder are NOT treated as
 * optional groups — they belong to the inner regex character class
 * (e.g. the `[0-9]` in `{id:[0-9]+}`). The implementation tracks the
 * brace depth and skips bracket characters while inside a placeholder.
 *
 * Recursive : multiple top-level optional segments produce the
 * cartesian product of their `with` / `without` variants. Returns
 * `[$pattern]` unchanged when the pattern carries no top-level
 * optional segment.
 *
 * @example
 * ```php
 * expandOptionalSegments( '/users' ) ;
 * // => [ '/users' ]
 *
 * expandOptionalSegments( '/users[/{id:[0-9]+}]' ) ;
 * // => [ '/users' , '/users/{id:[0-9]+}' ]
 *
 * expandOptionalSegments( '/a[/b][/c]' ) ;
 * // => [ '/a' , '/a/c' , '/a/b' , '/a/b/c' ]
 * ```
 *
 * @return array<int, string>
 *
 * @author  Marc Alcaraz
 * @package oihana\http\helpers
 */
function expandOptionalSegments( string $pattern ) :array
{
    $bracketStart = -1 ;
    $bracketDepth = 0 ;
    $braceDepth   = 0 ;
    $length       = strlen( $pattern ) ;

    for( $i = 0 ; $i < $length ; $i++ )
    {
        $char = $pattern[ $i ] ;

        if( $char === '{' )
        {
            $braceDepth++ ;
            continue ;
        }

        if( $char === '}' )
        {
            $braceDepth-- ;
            continue ;
        }

        if( $braceDepth !== 0 )
        {
            // Inside a placeholder regex (e.g. `[0-9]+`) — never a
            // top-level optional segment.
            continue ;
        }

        if( $char === '[' )
        {
            if( $bracketDepth === 0 && $bracketStart === -1 )
            {
                $bracketStart = $i ;
            }
            $bracketDepth++ ;
            continue ;
        }

        if( $char === ']' )
        {
            $bracketDepth-- ;

            if( $bracketDepth === 0 && $bracketStart !== -1 )
            {
                $without = substr( $pattern , 0 , $bracketStart ) . substr( $pattern , $i + 1 ) ;
                $with    = substr( $pattern , 0 , $bracketStart )
                         . substr( $pattern , $bracketStart + 1 , $i - $bracketStart - 1 )
                         . substr( $pattern , $i + 1 ) ;

                return array_merge
                (
                    expandOptionalSegments( $without ) ,
                    expandOptionalSegments( $with    ) ,
                ) ;
            }
        }
    }

    return [ $pattern ] ;
}
