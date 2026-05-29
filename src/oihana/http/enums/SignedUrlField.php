<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Query-string parameter names of the URL signing scheme shared by
 * `oihana\http\helpers\signatures\signUrl()` (which writes them) and
 * `oihana\http\helpers\signatures\verifySignedUrl()` (which reads
 * them).
 *
 * Both helpers must agree on these names: renaming one side without
 * the other would silently break verification (signed URLs would no
 * longer validate, with no error raised). Centralising the names
 * here keeps that contract in a single place.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\signatures\signUrl ;
 * use oihana\http\enums\SignedUrlField ;
 *
 * $url   = signUrl( 'https://example.com/file?id=42' , $secret , ttlSeconds: 600 ) ;
 * $query = parseQueryString( parse_url( $url , PHP_URL_QUERY ) ) ;
 *
 * $sig = $query[ SignedUrlField::SIGNATURE ][ 0 ] ; // base64url HMAC
 * $exp = $query[ SignedUrlField::EXPIRY    ][ 0 ] ; // Unix timestamp
 * ```
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class SignedUrlField
{
    use ConstantsTrait ;

    /**
     * The `sig` query parameter — carries the base64url-encoded HMAC.
     */
    public const string SIGNATURE = 'sig' ;

    /**
     * The `exp` query parameter — carries the absolute Unix
     * timestamp after which the signed URL is rejected.
     */
    public const string EXPIRY = 'exp' ;
}
