<?php

declare( strict_types = 1 );

namespace oihana\http\helpers ;

use InvalidArgumentException ;

/**
 * Compiles a Slim route pattern into a PHP regular expression with
 * named captures.
 *
 * Slim pattern grammar covered:
 * - **Static segments** (`/users/profile`) — escaped literally.
 * - **Bare placeholders** (`{name}`) — compiled to `(?P<name>[^/]+)`
 *   (any non-slash run, matching Slim's default semantics).
 * - **Constrained placeholders** (`{name:[0-9]+}`,
 *   `{ip:[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}}`) — the
 *   inner regex is passed through verbatim. Quantifier braces
 *   (`{1,3}`) are correctly tracked via brace-depth counting so
 *   they don't terminate the placeholder.
 * - **Optional segments** (`/users[/{id}]`) — top-level brackets
 *   compile to non-capturing `(?:...)?` groups. Brackets inside a
 *   `{...}` constraint belong to a regex character class and are
 *   left untouched (the placeholder is grabbed as a whole before
 *   the bracket-vs-optional distinction is made).
 *
 * The returned regex is anchored with `^...$` and uses `/` as the
 * delimiter — `preg_match()`-ready out of the box. Use
 * {@see matchSlimPattern()} for the common pattern → args
 * extraction flow.
 *
 * Example:
 * ```php
 * slimToRegex( '/users/{id:[0-9]+}' ) ;
 * // '/^\/users\/(?P<id>[0-9]+)$/'
 *
 * slimToRegex( '/users[/{id:[0-9]+}]' ) ;
 * // '/^\/users(?:\/(?P<id>[0-9]+))?$/'
 *
 * slimToRegex( '/static/path' ) ;
 * // '/^\/static\/path$/'
 * ```
 *
 * @param string $pattern The Slim route pattern.
 *
 * @return string A PHP regex (delimited and anchored).
 *
 * @throws InvalidArgumentException When the pattern carries an
 *                                  unmatched `{`.
 */
function slimToRegex( string $pattern ) :string
{
    $regex  = '' ;
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

            if ( $colon === false )
            {
                $name       = $inner ;
                // `/` is the regex delimiter — escape it inside the
                // character class so the compiled regex stays valid.
                $constraint = '[^\/]+' ;
            }
            else
            {
                $name = substr( $inner , 0 , $colon ) ;
                // Constraint authors typically write `[0-9]+` or
                // `[^/]+` without escaping `/`. Escape any unescaped
                // forward-slash so the compiled regex stays valid
                // regardless of the delimiter we chose.
                $constraint = preg_replace
                (
                    '#(?<!\\\\)/#' ,
                    '\\/' ,
                    substr( $inner , $colon + 1 ) ,
                ) ;
            }

            $regex .= '(?P<' . $name . '>' . $constraint . ')' ;
            $i      = $close + 1 ;
            continue ;
        }

        if ( $char === '[' )
        {
            $regex .= '(?:' ;
            $i++ ;
            continue ;
        }

        if ( $char === ']' )
        {
            $regex .= ')?' ;
            $i++ ;
            continue ;
        }

        $regex .= preg_quote( $char , '/' ) ;
        $i++ ;
    }

    return '/^' . $regex . '$/' ;
}

/**
 * Returns the position of the `}` that matches the `{` at
 * `$start`, tracking nested braces (so quantifier braces like
 * `{1,3}` inside a placeholder constraint do not break parsing).
 *
 * Internal helper. Public on purpose so the sibling helpers
 * ({@see slimToCasbinPattern()}) can share the same logic without
 * recopying it.
 *
 * @internal
 *
 * @param string $pattern The full pattern being walked.
 * @param int    $start   The position of the opening `{`.
 *
 * @return int The position of the matching `}`.
 *
 * @throws InvalidArgumentException When no matching `}` exists.
 */
function findSlimClosingBrace( string $pattern , int $start ) :int
{
    $depth  = 0 ;
    $length = strlen( $pattern ) ;

    for ( $i = $start ; $i < $length ; $i++ )
    {
        $char = $pattern[ $i ] ;

        if ( $char === '{' )
        {
            $depth++ ;
        }
        else if ( $char === '}' )
        {
            $depth-- ;
            if ( $depth === 0 )
            {
                return $i ;
            }
        }
    }

    throw new InvalidArgumentException
    (
        sprintf( 'Unmatched `{` at position %d in pattern: %s' , $start , $pattern )
    ) ;
}
