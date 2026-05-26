<?php

declare( strict_types = 1 );

namespace oihana\http\helpers ;

use InvalidArgumentException;

/**
 * Rewrites a Slim route pattern into its Casbin-canonical form by
 * replacing every `{name}` / `{name:regex}` placeholder with the
 * `:name` convention used by seeded permissions.
 *
 * Pure-string counterpart of {@see casbinRoutePattern()}: where the
 * Request-aware helper inspects the live Slim Route to recover the
 * argument map, this helper operates directly on the pattern
 * string — useful for offline seeding tooling
 * (`AuthRoutesDumpCommand`-style commands, OpenAPI exporters,
 * permission generators) that walk a route table without standing
 * up a request.
 *
 * Brace-depth tracking lets quantifier braces (`{1,3}`) inside a
 * placeholder constraint pass through cleanly:
 * `{ip:[0-9]{1,3}}` → `:ip`.
 *
 * Optional `[...]` segments are **preserved as-is** — the helper
 * does not expand them. Callers that need the cartesian product of
 * concrete variants should compose with
 * {@see expandOptionalSegments()} first:
 *
 * ```php
 * foreach ( expandOptionalSegments( '/users[/{id:[0-9]+}]' ) as $variant )
 * {
 *     $canonical = slimToCasbinPattern( $variant ) ;
 *     // '/users' or '/users/:id' — one Casbin policy entry each.
 * }
 * ```
 *
 * Example:
 * ```php
 * slimToCasbinPattern( '/users/{id:[0-9]+}' ) ;
 * // '/users/:id'
 *
 * slimToCasbinPattern( '/users/{id:[0-9]+}/posts/{slug}' ) ;
 * // '/users/:id/posts/:slug'
 *
 * slimToCasbinPattern( '/users[/{id}]' ) ;
 * // '/users[/:id]'  (brackets preserved)
 *
 * slimToCasbinPattern( '/static/path' ) ;
 * // '/static/path'  (no placeholders, untouched)
 * ```
 *
 * @param string $pattern The Slim route pattern.
 *
 * @return string The Casbin-canonical pattern.
 *
 * @throws InvalidArgumentException When the pattern carries an
 *                                   unmatched `{` (propagated from
 *                                   {@see findSlimClosingBrace()}).
 */
function slimToCasbinPattern( string $pattern ) :string
{
    $result = '' ;
    $i      = 0 ;
    $length = strlen( $pattern ) ;

    while ( $i < $length )
    {
        $char = $pattern[ $i ] ;

        if ( $char === '{' )
        {
            $close = findSlimClosingBrace( $pattern , $i ) ;
            $inner = substr( $pattern , $i + 1 , $close - $i - 1 ) ;
            $colon = strpos( $inner , ':' ) ;

            $name = $colon === false ? $inner : substr( $inner , 0 , $colon ) ;

            $result .= ':' . $name ;
            $i       = $close + 1 ;
            continue ;
        }

        $result .= $char ;
        $i++ ;
    }

    return $result ;
}
