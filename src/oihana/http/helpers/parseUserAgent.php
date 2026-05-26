<?php

namespace oihana\http\helpers ;

use ReflectionException ;

use xyz\oihana\schema\constants\http\DeviceType ;
use xyz\oihana\schema\http\UserAgentInfo ;

/**
 * Parses an HTTP `User-Agent` header string into a structured
 * {@see UserAgentInfo} DTO.
 *
 * Best-effort pragmatic parser, regex-based, with no external
 * dependency. Designed to cover the long-tail of common production
 * traffic (Chrome, Firefox, Safari, Edge, Opera; Windows, macOS,
 * Linux, Android, iOS, ChromeOS; the most prevalent search-engine
 * and social-media bots).
 *
 * This function is a thin orchestrator that composes the four
 * low-level detection helpers:
 * - {@see detectUserAgentBot()}
 * - {@see detectUserAgentBrowser()}
 * - {@see detectUserAgentOs()}
 * - {@see detectUserAgentDeviceType()}
 *
 * Callers that only need one signal (e.g. a boolean "is bot") can
 * use the dedicated helper directly and save a few regex passes.
 *
 * For exotic or rarely-seen agents the helper degrades gracefully:
 * unknown fields are returned as `null`, the device class falls back
 * to {@see DeviceType::UNKNOWN}, and the original `User-Agent`
 * string is always preserved verbatim in
 * {@see UserAgentInfo::$raw}.
 *
 * If you need exhaustive coverage (rare browsers, regional bots,
 * full device fingerprinting), plug a maintained UA database
 * (e.g. `ua-parser/uap-php`) on top of this helper — this lib is
 * deliberately dependency-free.
 *
 * Examples:
 * ```php
 * $info = parseUserAgent(
 *     'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) '
 *     . 'AppleWebKit/605.1.15 (KHTML, like Gecko) '
 *     . 'Version/17.5 Safari/605.1.15'
 * ) ;
 *
 * $info->browser    ; // 'Safari'
 * $info->os         ; // 'macOS'
 * $info->deviceType ; // 'desktop'
 * $info->isBot      ; // false
 * ```
 *
 * @param string|null $ua The `User-Agent` header value, or `null`
 *                        when the request did not carry one.
 *
 * @return UserAgentInfo Structured view (always returns a DTO, never
 *                       `null`; absent fields are exposed as `null`
 *                       inside the DTO).
 *
 * @throws ReflectionException Propagated from the
 *                             {@see UserAgentInfo} reflective
 *                             hydration in `org\schema\Thing`.
 */
function parseUserAgent( ?string $ua ) :UserAgentInfo
{
    if ( $ua === null || $ua === '' )
    {
        return new UserAgentInfo
        ([
            UserAgentInfo::RAW         => $ua                 ,
            UserAgentInfo::DEVICE_TYPE => DeviceType::UNKNOWN ,
            UserAgentInfo::IS_BOT      => false               ,
        ]) ;
    }

    $isBot = detectUserAgentBot( $ua ) ;

    [ $browser , $browserVersion ] = detectUserAgentBrowser( $ua ) ;
    [ $os      , $osVersion      ] = detectUserAgentOs( $ua ) ;

    $deviceType = $isBot
        ? DeviceType::BOT
        : detectUserAgentDeviceType( $ua , $os ) ;

    return new UserAgentInfo
    ([
        UserAgentInfo::BROWSER         => $browser        ,
        UserAgentInfo::BROWSER_VERSION => $browserVersion ,
        UserAgentInfo::OS              => $os             ,
        UserAgentInfo::OS_VERSION      => $osVersion      ,
        UserAgentInfo::DEVICE_TYPE     => $deviceType     ,
        UserAgentInfo::IS_BOT          => $isBot          ,
        UserAgentInfo::RAW             => $ua             ,
    ]) ;
}
