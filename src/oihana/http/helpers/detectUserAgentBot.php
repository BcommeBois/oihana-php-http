<?php

namespace oihana\http\helpers ;

/**
 * Tells whether a `User-Agent` string belongs to a bot, crawler or
 * automated agent.
 *
 * Low-level building block composed by {@see parseUserAgent()}, but
 * public so callers that only need the boolean answer can skip the
 * full parsing pass (rate-limiting, audit-log filtering, soft-paywall
 * heuristics).
 *
 * Detection is done in two passes:
 * - generic tokens (`bot/`, `-bot`, `crawl`, `spider`, `slurp`,
 *   `preview`) cover most search-engine and monitoring agents in
 *   one regex ;
 * - a named list catches well-known UA strings that do not carry
 *   the generic tokens (Googlebot, Bingbot, DuckDuckBot,
 *   facebookexternalhit, Twitterbot, LinkedInBot, Slackbot,
 *   curl, wget, python-requests, Go-http-client, okhttp, Java,
 *   ApacheBench, HeadlessChrome, PostmanRuntime, Pingdom, …).
 *
 * The check is intentionally permissive — false positives on a
 * "bot"-looking UA are cheaper than false negatives that let
 * automation through unnoticed.
 *
 * For the predicate version that accepts `null` / empty inputs,
 * see {@see isBotUserAgent()}.
 *
 * @param string $ua The `User-Agent` header value (non-empty).
 *
 * @return bool `true` when the UA matches a known bot pattern.
 */
function detectUserAgentBot( string $ua ) :bool
{
    // Generic tokens cover most search-engine and monitoring bots in
    // one pass: -bot, bot/, crawl, spider, slurp (Yahoo), preview.
    if ( preg_match( '/(bot[\s\/_-]|crawl|spider|slurp|preview)/i' , $ua ) === 1 )
    {
        return true ;
    }

    // Named bots / tools that do not carry the generic tokens above.
    $named =
    [
        'Googlebot' , 'Bingbot' , 'DuckDuckBot' , 'Baiduspider' ,
        'YandexBot' , 'facebookexternalhit' , 'Twitterbot' ,
        'LinkedInBot' , 'Slackbot' , 'TelegramBot' , 'WhatsApp' ,
        'AhrefsBot' , 'SemrushBot' , 'MJ12bot' ,
        'curl/' , 'Wget/' , 'python-requests' , 'Go-http-client' ,
        'okhttp' , 'Java/' , 'ApacheBench' , 'PostmanRuntime' ,
        'HeadlessChrome' , 'PhantomJS' , 'Pingdom' , 'UptimeRobot' ,
    ] ;

    return array_any( $named , fn( $needle ) => stripos( $ua , $needle ) !== false );
}
