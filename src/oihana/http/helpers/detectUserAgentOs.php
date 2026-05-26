<?php

namespace oihana\http\helpers ;

use oihana\http\enums\OsName ;

/**
 * Extracts the operating system family and version from a
 * `User-Agent` string.
 *
 * Low-level building block composed by {@see parseUserAgent()}.
 * Public so callers that only need the OS breakdown can skip the
 * browser / device-type detection passes.
 *
 * Windows NT versions are remapped to their marketing equivalent
 * (`NT 10.0` → `10`, `NT 6.3` → `8.1`, `NT 6.1` → `7`, …) because
 * that is what most developers actually want to read. macOS and
 * iOS underscores are normalised to dots (`14_5` → `14.5`).
 * Android exposes the raw version (`Android 13`).
 *
 * Detection order matters:
 * - {@see OsName::WINDOWS} first (`Windows NT <ver>`).
 * - {@see OsName::IPADOS} before {@see OsName::IOS}: iPad UAs
 *   carry `CPU OS` but no `iPhone` token.
 * - {@see OsName::ANDROID} before {@see OsName::LINUX}: Android UAs
 *   contain `Linux` too.
 * - {@see OsName::MACOS} (`Mac OS X` or bare `Macintosh`).
 * - {@see OsName::CHROME_OS} (`CrOS` token).
 * - {@see OsName::LINUX} as final fallback.
 *
 * Returns a tuple `[name, version]`. Either element may be `null`
 * when the corresponding signal cannot be extracted. `name` matches
 * a constant of {@see OsName} when present.
 *
 * Example:
 * ```php
 * detectUserAgentOs(
 *     'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 …'
 * ) ;
 * // [ 'macOS' , '14.5' ]
 * ```
 *
 * @param string $ua The `User-Agent` header value (non-empty).
 *
 * @return array{0: string|null, 1: string|null} `[name, version]`.
 */
function detectUserAgentOs( string $ua ) :array
{
    if ( preg_match( '/Windows NT ([\d.]+)/' , $ua , $m ) === 1 )
    {
        $nt = $m[ 1 ] ;
        $map =
        [
            '10.0' => '10'    ,
            '6.3'  => '8.1'   ,
            '6.2'  => '8'     ,
            '6.1'  => '7'     ,
            '6.0'  => 'Vista' ,
            '5.2'  => 'XP'    ,
            '5.1'  => 'XP'    ,
            '5.0'  => '2000'  ,
        ] ;
        return [ OsName::WINDOWS , $map[ $nt ] ?? $nt ] ;
    }

    // iPadOS / iOS: bracket order matters — iPad first because iPad
    // UAs also contain "CPU OS" but no "iPhone" token.
    if ( preg_match( '/iPad;\s*CPU OS ([\d_]+)/' , $ua , $m ) === 1 )
    {
        return [ OsName::IPADOS , str_replace( '_' , '.' , $m[ 1 ] ) ] ;
    }

    if ( preg_match( '/(?:iPhone|iPod)[^;]*;\s*CPU iPhone OS ([\d_]+)/' , $ua , $m ) === 1 )
    {
        // iPod UAs carry `iPod touch;` (with the trailing space + model
        // name) — the `[^;]*` swallows that without affecting standard
        // `iPhone;` UAs.
        return [ OsName::IOS , str_replace( '_' , '.' , $m[ 1 ] ) ] ;
    }

    if ( preg_match( '/Android ([\d.]+)/' , $ua , $m ) === 1 )
    {
        return [ OsName::ANDROID , $m[ 1 ] ] ;
    }

    // macOS — Mac OS X is the historical product name string carried
    // by every Safari / Chrome UA on macOS.
    if ( preg_match( '/Mac OS X ([\d_.]+)/' , $ua , $m ) === 1 )
    {
        return [ OsName::MACOS , str_replace( '_' , '.' , $m[ 1 ] ) ] ;
    }

    if ( stripos( $ua , 'Macintosh' ) !== false )
    {
        return [ OsName::MACOS , null ] ;
    }

    if ( stripos( $ua , 'CrOS' ) !== false )
    {
        return [ OsName::CHROME_OS , null ] ;
    }

    // Linux — last because Android UAs also contain "Linux".
    if ( stripos( $ua , 'Linux' ) !== false )
    {
        return [ OsName::LINUX , null ] ;
    }

    return [ null , null ] ;
}
