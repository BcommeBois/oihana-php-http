<?php

namespace oihana\http\helpers\auth ;

use oihana\enums\http\AuthScheme ;
use oihana\enums\http\HttpHeader ;
use oihana\http\enums\AuthorizationField ;
use oihana\http\enums\BasicAuthField ;

use Psr\Http\Message\ServerRequestInterface ;

/**
 * Extracts the username / password pair from a PSR-7 request that
 * carries a `Basic` HTTP authentication header (RFC 7617).
 *
 * Reads the `Authorization` header, delegates parsing to
 * {@see parseAuthorizationHeader()}, base64-decodes the credentials
 * and splits on the **first** colon (the password may itself
 * contain colons — only the first separator counts per RFC 7617
 * §2).
 *
 * Returns `null` when:
 * - the `Authorization` header is missing or empty ;
 * - the scheme is anything other than `Basic` ;
 * - the credentials are not valid strict base64 ;
 * - the decoded payload does not contain `:`.
 *
 * Both the username and the password may be empty strings (legal
 * per the RFC — `Basic Og==` decodes to `':'`, i.e. empty / empty).
 *
 * Example:
 * ```php
 * $creds = getBasicAuth( $request ) ;
 *
 * if ( $creds !== null
 *      && $creds[ BasicAuthField::USER ] === $expectedUser
 *      && hash_equals( $expectedHash , password_hash_verify( $creds[ BasicAuthField::PASS ] ) ) )
 * {
 *     // authenticated
 * }
 * ```
 *
 * @param ServerRequestInterface $request The PSR-7 request.
 *
 * @return array{user: string, pass: string}|null
 */
function getBasicAuth( ServerRequestInterface $request ) :?array
{
    $parsed = parseAuthorizationHeader( $request->getHeaderLine( HttpHeader::AUTHORIZATION ) ) ;

    if ( $parsed === null )
    {
        return null ;
    }

    if ( strcasecmp( $parsed[ AuthorizationField::SCHEME ] , AuthScheme::BASIC ) !== 0 )
    {
        return null ;
    }

    $decoded = base64_decode( $parsed[ AuthorizationField::CREDENTIALS ] , true ) ;

    if ( $decoded === false )
    {
        return null ;
    }

    $colon = strpos( $decoded , ':' ) ;

    if ( $colon === false )
    {
        return null ;
    }

    return
    [
        BasicAuthField::USER => substr( $decoded , 0 , $colon ) ,
        BasicAuthField::PASS => substr( $decoded , $colon + 1 ) ,
    ] ;
}
