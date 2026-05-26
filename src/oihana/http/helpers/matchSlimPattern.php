<?php

declare( strict_types = 1 );

namespace oihana\http\helpers ;

/**
 * Matches a request path against a Slim route pattern and returns
 * the captured arguments.
 *
 * Pure pattern → args extractor — no PSR-7 request, no FastRoute
 * cache, no Slim container required. Compiles the pattern via
 * {@see slimToRegex()} (which handles bare placeholders,
 * constrained placeholders and optional segments), runs
 * `preg_match()`, then walks the named captures.
 *
 * Returns:
 * - `null` when the path does not match the pattern at all ;
 * - an associative array of captured arguments otherwise. Optional
 *   placeholders that did not match are **omitted** from the
 *   result (rather than mapped to an empty string), so callers
 *   can use `isset()` / `array_key_exists()` to test presence.
 *
 * Use cases:
 * - permission seeding from a route table without a live request
 *   (Casbin policy generation, `AuthRoutesDumpCommand`-style
 *   tooling, OpenAPI generation) ;
 * - unit-testing Slim patterns in isolation ;
 * - lightweight routing in non-Slim entry points.
 *
 * Example:
 * ```php
 * matchSlimPattern( '/users/{id:[0-9]+}' , '/users/42' ) ;
 * // [ 'id' => '42' ]
 *
 * matchSlimPattern( '/users/{id:[0-9]+}' , '/users/abc' ) ;
 * // null  (constraint failed)
 *
 * matchSlimPattern( '/users[/{id:[0-9]+}]' , '/users' ) ;
 * // []  (matched, no args captured)
 *
 * matchSlimPattern( '/users[/{id:[0-9]+}]' , '/users/42' ) ;
 * // [ 'id' => '42' ]
 *
 * matchSlimPattern( '/users/{id}' , '/posts/42' ) ;
 * // null
 * ```
 *
 * @param string $pattern The Slim route pattern.
 * @param string $path    The resolved request path.
 *
 * @return array<string, string>|null The captured arguments, or
 *                                    `null` when the path does not
 *                                    match.
 */
function matchSlimPattern( string $pattern , string $path ) :?array
{
    $regex = slimToRegex( $pattern ) ;

    if ( preg_match( $regex , $path , $matches ) !== 1 )
    {
        return null ;
    }

    $args = [] ;

    foreach ( $matches as $key => $value )
    {
        // Skip numeric indices — keep only named captures.
        if ( !is_string( $key ) )
        {
            continue ;
        }

        // Unmatched optional groups appear as empty strings in
        // $matches; skip them so callers can use isset() to test
        // for presence.
        if ( $value === '' )
        {
            continue ;
        }

        $args[ $key ] = $value ;
    }

    return $args ;
}
