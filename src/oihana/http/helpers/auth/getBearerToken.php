<?php

namespace oihana\http\helpers\auth ;

use oihana\enums\http\AuthScheme ;
use oihana\enums\http\HttpHeader ;
use oihana\http\enums\AuthorizationField ;

use Psr\Http\Message\ServerRequestInterface ;

/**
 * Extracts the Bearer access token from a PSR-7 request.
 *
 * Reads the `Authorization` header, delegates parsing to
 * {@see parseAuthorizationHeader()}, and returns the credentials
 * portion when (and only when) the scheme is
 * {@see AuthScheme::BEARER} (case-insensitive).
 *
 * Returns `null` when:
 * - the `Authorization` header is missing or empty ;
 * - the scheme is anything other than `Bearer` ;
 * - the credentials part is empty (`Bearer` with no token).
 *
 * Example:
 * ```php
 * $token = getBearerToken( $request ) ;
 *
 * if ( $token === null )
 * {
 *     return new Response( 401 ) ;
 * }
 *
 * $claims = $jwt->decode( $token ) ;
 * ```
 *
 * @param ServerRequestInterface $request The PSR-7 request.
 *
 * @return string|null The Bearer token, or `null` when none could
 *                     be extracted.
 */
function getBearerToken( ServerRequestInterface $request ) :?string
{
    $parsed = parseAuthorizationHeader( $request->getHeaderLine( HttpHeader::AUTHORIZATION ) ) ;

    if ( $parsed === null )
    {
        return null ;
    }

    if ( strcasecmp( $parsed[ AuthorizationField::SCHEME ] , AuthScheme::BEARER ) !== 0 )
    {
        return null ;
    }

    $credentials = $parsed[ AuthorizationField::CREDENTIALS ] ;

    return $credentials !== '' ? $credentials : null ;
}
