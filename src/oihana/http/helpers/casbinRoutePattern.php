<?php

declare( strict_types = 1 );

namespace oihana\http\helpers;

use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Slim\Routing\RouteContext;

/**
 * Returns the Casbin-canonical route template for the incoming request.
 *
 * Seeded permissions use the `:name` convention (e.g. `/customers/:id`)
 * — the same convention emitted by `AuthGeneratePermissionsCommand`.
 * This helper rewrites the resolved request path back to that template
 * so a strict `r.obj == p.obj` comparison in Casbin (no `keyMatch2`)
 * matches the seeded permission unambiguously.
 *
 * The implementation walks the request path segment by segment and
 * substitutes any segment whose value was captured as a Slim route
 * argument with `:<argName>`. This sidesteps two Slim pattern subtleties
 * that bit the naive `preg_replace` approach:
 *
 *  - `{id:[0-9]+}` — Slim regex constraint, must collapse to `:id`.
 *  - `[/{id:...}]` — Slim optional segment syntax. The brackets are part
 *    of the raw pattern returned by `Route::getPattern()` and must not
 *    leak into the Casbin object. With the segment walk we never look at
 *    the raw pattern, so the brackets are naturally invisible.
 *
 * Without this helper, the previous matcher relied on
 * `keyMatch2(r.obj, p.obj)` to bridge the resolved path
 * (`/customers/123`) and the seeded pattern (`/customers/:id`).
 * `keyMatch2` treats `:id` as a wildcard for any single segment,
 * which silently allowed `/customers/count` to be authorised by
 * the `customers:get` permission. Canonicalising the request to
 * its route template removes the need for any wildcard matcher.
 *
 * Falls back to the raw request path when no Slim route is attached
 * (e.g. 404, or invocation outside the regular middleware stack).
 *
 * @example
 * ```php
 * // GET /customers/123 (Slim pattern: /customers/{id:[0-9]+})
 * casbinRoutePattern( $request ) ; // '/customers/:id'
 *
 * // DELETE /policies/123 (Slim pattern: /policies[/{id:[0-9]+}])
 * casbinRoutePattern( $request ) ; // '/policies/:id'
 *
 * // GET /customers/count (Slim pattern: /customers/count, no args)
 * casbinRoutePattern( $request ) ; // '/customers/count'
 * ```
 *
 * @author  Marc Alcaraz
 * @package oihana\http\helpers
 */
function casbinRoutePattern( Request $request ) :string
{
    $path = $request->getUri()->getPath() ;

    try
    {
        $route = RouteContext::fromRequest( $request )->getRoute() ;
    }
    catch( RuntimeException )
    {
        return $path ;
    }

    if( $route === null )
    {
        return $path ;
    }

    return casbinCanonicalisePath( $path , $route->getArguments() ) ;
}

/**
 * Pure helper that rewrites a resolved request path into its Casbin
 * canonical form using a `name => value` map of route arguments.
 *
 * Extracted from {@see casbinRoutePattern()} so the substitution logic
 * can be unit-tested in isolation without standing up a Slim Route.
 * Public on purpose for that reason.
 *
 * @param string                $path Resolved request path, e.g. `/policies/75459030`.
 * @param array<string, string> $args Slim route arguments, e.g. `['id' => '75459030']`.
 *
 * @return string Canonical path, e.g. `/policies/:id`.
 */
function casbinCanonicalisePath( string $path , array $args ) :string
{
    if( $args === [] )
    {
        return $path ;
    }

    $segments = explode( '/' , $path ) ;

    foreach( $segments as &$segment )
    {
        foreach( $args as $name => $value )
        {
            if( $segment !== '' && $segment === $value )
            {
                $segment = ':' . $name ;
                break ;
            }
        }
    }
    unset( $segment ) ;

    return implode( '/' , $segments ) ;
}
