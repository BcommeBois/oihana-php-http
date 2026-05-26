<?php

namespace oihana\http\helpers ;

use ReflectionException;
use xyz\oihana\schema\constants\http\DeviceType ;

/**
 * Tells whether a `User-Agent` string belongs to a mobile form factor.
 *
 * Returns `true` for both **mobile** (smartphone) and **tablet**
 * devices, matching the popular convention used by `Mobile_Detect`
 * and similar libraries: the typical caller question is "should I
 * serve a mobile-friendly UI?", and tablets are usually grouped
 * with phones in that decision.
 *
 * If you specifically need phones only or tablets only, call
 * {@see parseUserAgent()} directly and inspect the `deviceType`
 * field against {@see DeviceType::MOBILE} / {@see DeviceType::TABLET}.
 *
 * Bots, desktops and unknown devices return `false`.
 *
 * @param string|null $ua The User-Agent header value, or `null`.
 *
 * @return bool `true` when the UA matches a mobile or tablet form factor.
 *
 * @throws ReflectionException
 */
function isMobileUserAgent( ?string $ua ) :bool
{
    $type = parseUserAgent( $ua )->deviceType ?? null ;

    return $type === DeviceType::MOBILE
        || $type === DeviceType::TABLET ;
}
