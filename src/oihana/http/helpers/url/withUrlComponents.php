<?php

namespace oihana\http\helpers\url ;

/**
 * Returns a copy of a URL with some of its components replaced or removed.
 *
 * The URL is parsed with `parse_url()`, the given `$overrides` are merged
 * into the parsed components, then the whole thing is reassembled. Use it to
 * derive a URL from another by changing only what you need — the scheme, the
 * password, the host… — while leaving the rest untouched.
 *
 * Keys of `$overrides` are the `parse_url()` component names, available as the
 * `oihana\enums\http\UrlComponent` constants (`SCHEME`, `HOST`, `PORT`,
 * `USER`, `PASS`, `PATH`, `QUERY`, `FRAGMENT`). A value of `null` **removes**
 * that component. Unknown keys are ignored.
 *
 * Examples:
 * ```php
 * use oihana\enums\http\UrlComponent ;
 * use function oihana\http\helpers\url\withUrlComponents ;
 *
 * // Switch scheme only
 * withUrlComponents( 'http://example.com/path' , [ UrlComponent::SCHEME => 'https' ] ) ;
 * // 'https://example.com/path'
 *
 * // Replace the password only
 * withUrlComponents( 'https://user:old@example.com' , [ UrlComponent::PASS => 'new' ] ) ;
 * // 'https://user:new@example.com'
 *
 * // Remove the query and the fragment
 * withUrlComponents( 'https://example.com/p?x=1#frag' , [ UrlComponent::QUERY => null , UrlComponent::FRAGMENT => null ] ) ;
 * // 'https://example.com/p'
 * ```
 *
 * Limits (deliberately out of scope — same pragmatic stance as
 * {@see normalizeUrl()}):
 * - **No percent-encoding.** Override values are inserted verbatim. A value
 *   carrying reserved characters (a password with `@` or `:`, a path with a
 *   space…) must be percent-encoded by the caller, otherwise the produced URL
 *   will be malformed.
 * - **No well-formedness check.** The result is not re-parsed or validated.
 * - **IPv6 host keeps its brackets.** To set an IPv6 host literal, pass it
 *   bracketed (`[::1]`). Mind the asymmetry with {@see getHost()}, which strips
 *   them: `getHost()`'s output is meant for inspection, not for feeding back
 *   here.
 * - **Authority-bound components.** `user` / `pass` / `port` are only emitted
 *   when a host is present; removing the host (`HOST => null`) drops them from
 *   the output even if they are still set in `$overrides` or the source URL.
 * - **Fail-open.** If `parse_url()` cannot parse `$url`, it is returned
 *   untouched and the overrides are ignored.
 *
 * @param string                         $url       The source URL.
 * @param array<string, string|int|null> $overrides Component overrides keyed by
 *                                                   `UrlComponent` constants;
 *                                                   `null` removes the component.
 *
 * @return string The derived URL, or `$url` untouched when it cannot be parsed.
 */
function withUrlComponents( string $url , array $overrides ) :string
{
    $parts = parse_url( $url ) ;

    if ( $parts === false )
    {
        return $url ;
    }

    foreach ( $overrides as $component => $value )
    {
        if ( $value === null )
        {
            unset( $parts[ $component ] ) ;
        }
        else
        {
            $parts[ $component ] = $value ;
        }
    }

    return reassembleUrl( $parts ) ;
}
