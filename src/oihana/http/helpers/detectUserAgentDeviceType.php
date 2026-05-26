<?php

namespace oihana\http\helpers ;

use oihana\http\enums\OsName ;
use xyz\oihana\schema\constants\http\DeviceType ;

/**
 * Heuristically classifies the device family from the User-Agent
 * tokens and the previously detected OS.
 *
 * Low-level building block composed by {@see parseUserAgent()}.
 * Public so callers that only need the device class can skip the
 * browser / bot detection passes — but most application code should
 * prefer {@see parseUserAgent()} so the bot flag still influences
 * the result via {@see DeviceType::BOT}.
 *
 * Order of checks matters:
 * - {@see OsName::IPADOS} / explicit `iPad` token → {@see DeviceType::TABLET}.
 * - {@see OsName::ANDROID} with an explicit `Tablet` token (or no
 *   `Mobile` token) → {@see DeviceType::TABLET}.
 * - {@see OsName::IOS} / `iPhone` / `iPod` / `Mobile` token →
 *   {@see DeviceType::MOBILE}.
 * - Desktop OS families ({@see OsName::WINDOWS},
 *   {@see OsName::MACOS}, {@see OsName::LINUX},
 *   {@see OsName::CHROME_OS}) → {@see DeviceType::DESKTOP}.
 * - Anything else → {@see DeviceType::UNKNOWN}.
 *
 * Known limit: recent iPadOS reports a macOS-like UA with a
 * `Macintosh` token and no `iPad` hint — those iPads will be
 * misclassified as desktop. UA-Client-Hints
 * (`Sec-CH-UA` / `Sec-CH-UA-Mobile`) are the only reliable signal
 * there.
 *
 * @param string      $ua The `User-Agent` header value (non-empty).
 * @param string|null $os The OS detected by {@see detectUserAgentOs()},
 *                        or `null` when no OS could be identified.
 *                        Must match an {@see OsName} constant when
 *                        non-null.
 *
 * @return string One of the {@see DeviceType} constants.
 */
function detectUserAgentDeviceType( string $ua , ?string $os ) :string
{
    if ( $os === OsName::IPADOS || stripos( $ua , 'iPad' ) !== false )
    {
        return DeviceType::TABLET ;
    }

    if ( $os === OsName::ANDROID && stripos( $ua , 'Mobile' ) === false )
    {
        // Android without "Mobile" token is, by convention, a tablet.
        return DeviceType::TABLET ;
    }

    if ( $os === OsName::IOS || stripos( $ua , 'iPhone' ) !== false || stripos( $ua , 'iPod' ) !== false )
    {
        return DeviceType::MOBILE ;
    }

    if ( $os === OsName::ANDROID || stripos( $ua , 'Mobile' ) !== false )
    {
        return DeviceType::MOBILE ;
    }

    $desktop =
    [
        OsName::WINDOWS  ,
        OsName::MACOS    ,
        OsName::LINUX    ,
        OsName::CHROME_OS ,
    ] ;

    if ( in_array( $os , $desktop , true ) )
    {
        return DeviceType::DESKTOP ;
    }

    return DeviceType::UNKNOWN ;
}
