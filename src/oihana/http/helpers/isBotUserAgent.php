<?php

namespace oihana\http\helpers ;

use ReflectionException;

/**
 * Tells whether a `User-Agent` string belongs to a bot, crawler or
 * automated agent.
 *
 * Thin wrapper around {@see parseUserAgent()} for the common case of
 * a single boolean answer (rate-limiting decisions, audit log
 * filtering, soft-paywall heuristics…).
 *
 * `null` and empty input return `false` — the absence of a UA is
 * not, by itself, a bot signal.
 *
 * @param string|null $ua The User-Agent header value, or `null`.
 *
 * @return bool `true` when the UA matches a known bot / crawler / automation pattern.
 *
 * @throws ReflectionException
 */
function isBotUserAgent( ?string $ua ) :bool
{
    return parseUserAgent( $ua )->isBot ?? false ;
}
