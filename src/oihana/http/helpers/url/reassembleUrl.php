<?php

namespace oihana\http\helpers\url ;

use oihana\enums\http\UrlComponent ;

/**
 * Reassembles a `parse_url`-shaped associative array back into a URL string.
 *
 * Shared low-level building block behind {@see normalizeUrl()} and {@see withUrlComponents()}.
 * Components are emitted in canonical RFC 3986 order;
 * `user` / `pass` / `port` are only emitted when a `host` is present
 * (they belong to the authority). Values are written **verbatim** — no
 * percent-encoding is applied, so callers are responsible for passing
 * already-encoded components.
 *
 * @internal Not part of the public API — operates on a raw `parse_url()` array.
 *
 * @param array<string, string|int> $parts
 *
 * @return string
 */
function reassembleUrl( array $parts ) :string
{
    $url = '' ;

    if ( isset( $parts[ UrlComponent::SCHEME ] ) )
    {
        $url .= $parts[ UrlComponent::SCHEME ] . ':' ;
    }

    if ( isset( $parts[ UrlComponent::HOST ] ) )
    {
        $url .= '//' ;

        if ( isset( $parts[ UrlComponent::USER ] ) )
        {
            $url .= $parts[ UrlComponent::USER ] ;
            if ( isset( $parts[ UrlComponent::PASS ] ) )
            {
                $url .= ':' . $parts[ UrlComponent::PASS ] ;
            }
            $url .= '@' ;
        }

        $url .= $parts[ UrlComponent::HOST ] ;

        if ( isset( $parts[ UrlComponent::PORT ] ) )
        {
            $url .= ':' . $parts[ UrlComponent::PORT ] ;
        }
    }

    if ( isset( $parts[ UrlComponent::PATH ] ) )
    {
        $url .= $parts[ UrlComponent::PATH ] ;
    }

    if ( isset( $parts[ UrlComponent::QUERY ] ) && $parts[ UrlComponent::QUERY ] !== '' )
    {
        $url .= '?' . $parts[ UrlComponent::QUERY ] ;
    }

    if ( isset( $parts[ UrlComponent::FRAGMENT ] ) )
    {
        $url .= '#' . $parts[ UrlComponent::FRAGMENT ] ;
    }

    return $url ;
}
