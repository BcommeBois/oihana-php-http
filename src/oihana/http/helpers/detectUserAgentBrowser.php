<?php

namespace oihana\http\helpers ;

use oihana\http\enums\BrowserName ;

/**
 * Extracts the browser product and version from a `User-Agent`
 * string.
 *
 * Low-level building block composed by {@see parseUserAgent()}.
 * Public so callers that only need the browser breakdown can skip
 * the OS / device-type detection passes.
 *
 * Detection order matters and is **not commutative**:
 * - {@see BrowserName::EDGE}, {@see BrowserName::OPERA},
 *   {@see BrowserName::VIVALDI} and {@see BrowserName::FIREFOX} are
 *   tested first because they carry the brand token alongside the
 *   `Chrome/...` token (every Chromium-based browser inherits the
 *   `Chrome/...` tail).
 * - {@see BrowserName::CHROME} is matched next.
 * - {@see BrowserName::IE} catches legacy `MSIE` / `Trident` UAs.
 * - {@see BrowserName::SAFARI} is the final fallback: its UA always
 *   carries `Version/X.Y Safari/<webkit-build>`, so we read the
 *   product version from the `Version/...` token.
 *
 * Returns a tuple `[name, version]`. Either element may be `null`
 * when the corresponding signal cannot be extracted. `name` matches
 * a constant of {@see BrowserName} when present.
 *
 * Example:
 * ```php
 * detectUserAgentBrowser(
 *     'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) '
 *     . 'Chrome/126.0.6478.127 Safari/537.36'
 * ) ;
 * // [ 'Chrome' , '126.0.6478.127' ]
 * ```
 *
 * @param string $ua The `User-Agent` header value (non-empty).
 *
 * @return array{0: string|null, 1: string|null} `[name, version]`.
 */
function detectUserAgentBrowser( string $ua ) :array
{
    // Order matters: Chromium-based browsers all carry `Chrome/...`
    // in their UA; we have to filter Edge/Opera/Vivaldi first.
    $patterns =
    [
        BrowserName::EDGE    => '/(?:Edg|EdgA|EdgiOS|Edge)\/([\d.]+)/' ,
        BrowserName::OPERA   => '/(?:OPR|Opera)\/([\d.]+)/' ,
        BrowserName::VIVALDI => '/Vivaldi\/([\d.]+)/' ,
        BrowserName::FIREFOX => '/(?:Firefox|FxiOS)\/([\d.]+)/' ,
        BrowserName::CHROME  => '/(?:Chrome|CriOS|Chromium)\/([\d.]+)/' ,
        BrowserName::IE      => '/(?:MSIE\s|Trident\/[\d.]+;\s*rv:)([\d.]+)/' ,
    ] ;

    foreach ( $patterns as $name => $regex )
    {
        if ( preg_match( $regex , $ua , $m ) === 1 )
        {
            return [ $name , $m[ 1 ] ] ;
        }
    }

    // Safari last: its UA carries `Version/X.Y Safari/Z`, where the
    // Safari/Z token is the WebKit build, not the product version.
    if ( preg_match( '/Version\/([\d.]+)\s+(?:Mobile\/\w+\s+)?Safari\//' , $ua , $m ) === 1 )
    {
        return [ BrowserName::SAFARI , $m[ 1 ] ] ;
    }

    if ( stripos( $ua , 'Safari/' ) !== false )
    {
        return [ BrowserName::SAFARI , null ] ;
    }

    return [ null , null ] ;
}
