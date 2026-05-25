<?php

namespace oihana\http\helpers\ips ;

use Psr\Http\Message\ServerRequestInterface ;

/**
 * Returns the client IP address in canonical form.
 *
 * Default resolution order (no options provided — preserves the legacy
 * behaviour expected by every existing caller):
 * - {@code CF-Connecting-IP}
 * - {@code X-Forwarded-For} (first entry)
 * - {@code X-Real-IP}
 * - {@code REMOTE_ADDR}
 *
 * Supports:
 * - PSR-7 requests (preferred when {@code $request} is provided) ;
 * - Native `$_SERVER` fallback when no request is supplied ;
 * - Optional RFC 7239 {@code Forwarded} header parsing ;
 * - Optional trusted-proxy filtering (anti-spoofing) ;
 * - Optional rejection of private / reserved ranges.
 *
 * Notes:
 * - The first IP of {@code X-Forwarded-For} (left-most) is used. When a
 *   trusted-proxy list is provided, the standard reverse-proxy walk is
 *   applied instead: the chain is parsed right-to-left and the first
 *   non-trusted entry is returned as the client.
 * - Invalid IP addresses are silently skipped.
 * - When {@code $allowPrivate} is `false`, only publicly routable
 *   addresses are returned ({@see isPublicIp()}).
 * - All returned IPs are normalised via {@see canonicalIp()}.
 *
 * Trusted-proxy semantics:
 * - Empty list → behave like the legacy helper, fully trust headers.
 * - Non-empty list → headers are honoured **only** when the direct hop
 *   ({@code REMOTE_ADDR}) belongs to the list. Otherwise the helper
 *   ignores every proxy header and returns {@code REMOTE_ADDR} itself
 *   (subject to the {@code $allowPrivate} filter).
 * - Each entry may be a bare IP or a CIDR range (IPv4 or IPv6).
 *
 * @param ServerRequestInterface|null $request        Optional PSR-7 request.
 * @param string[]                    $trustedProxies Bare IPs or CIDR ranges.
 * @param bool                        $allowPrivate   When `false`, reject private / reserved ranges.
 * @param bool                        $useForwarded   When `true`, parse the RFC 7239 {@code Forwarded} header.
 *
 * @return string|null The normalized client IP address, or null when none can be resolved.
 */
function getClientIp
(
    ?ServerRequestInterface $request        = null  ,
    array                   $trustedProxies = []    ,
    bool                    $allowPrivate   = true  ,
    bool                    $useForwarded   = false ,
): ?string
{
    [ $remoteAddr , $headerCandidates , $forwardedChain ] = $request
        ? extractIpCandidatesFromRequest( $request , $useForwarded )
        : extractIpCandidatesFromGlobals( $useForwarded ) ;

    // ===================================================
    // Trusted-proxy mode (anti-spoofing)
    // ===================================================

    if ( $trustedProxies !== [] )
    {
        if ( $remoteAddr === null || !ipInList( $remoteAddr , $trustedProxies ) )
        {
            // Direct hop is not a trusted proxy → ignore every header.
            return acceptIp( $remoteAddr , $allowPrivate ) ;
        }

        // Walk the X-Forwarded-For / Forwarded chain right-to-left,
        // skipping every trusted hop. The first non-trusted entry
        // is the real client.
        $candidate = walkForwardedChain( $forwardedChain , $trustedProxies ) ;

        if ( $candidate !== null )
        {
            $accepted = acceptIp( $candidate , $allowPrivate ) ;
            if ( $accepted !== null )
            {
                return $accepted ;
            }
        }

        // Also accept other proxy headers (CF, X-Real-IP) when the
        // direct hop is trusted — they are still spoofable in absolute
        // terms but assumed to be set by the trusted edge.
        foreach ( $headerCandidates as $candidate )
        {
            $accepted = acceptIp( $candidate , $allowPrivate ) ;
            if ( $accepted !== null )
            {
                return $accepted ;
            }
        }

        return acceptIp( $remoteAddr , $allowPrivate ) ;
    }

    // ===================================================
    // Legacy mode (no trusted-proxy list)
    // ===================================================

    foreach ( $headerCandidates as $candidate )
    {
        $accepted = acceptIp( $candidate , $allowPrivate ) ;
        if ( $accepted !== null )
        {
            return $accepted ;
        }
    }

    return acceptIp( $remoteAddr , $allowPrivate ) ;
}
