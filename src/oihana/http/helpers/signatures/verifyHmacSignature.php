<?php

declare( strict_types = 1 );

namespace oihana\http\helpers\signatures ;

use oihana\enums\HashAlgorithm ;
use oihana\http\enums\SignatureFormat ;

use function oihana\core\encoding\base64UrlEncode ;

/**
 * Verifies an HMAC signature against a raw payload — the building
 * block of webhook authentication (Stripe, GitHub, Slack,
 * Mailchimp, …).
 *
 * Constant-time comparison via `hash_equals()` — safe against
 * timing-side-channel attacks.
 *
 * The `$format` parameter must match the format used by the
 * sender:
 * - `'hex'` (default) — lowercased hexadecimal, the most common
 *   choice (GitHub `X-Hub-Signature-256`, Slack `X-Slack-Signature`,
 *   Mailchimp, …).
 * - `'base64'` — standard base64 with `+` / `/` and `=` padding.
 * - `'base64url'` — RFC 4648 §5 base64url, `-` / `_` without
 *   padding.
 *
 * Vendor-specific prefixes and envelope formats (e.g. Stripe's
 * `t=…,v1=…` `Stripe-Signature`, GitHub's `sha256=…` prefix,
 * Slack's `v0=…:…`) are **NOT** handled here — strip the
 * envelope to get the raw signature value first, then call this
 * helper. This keeps the function focused on the cryptographic
 * primitive and lets each integration deal with its own framing.
 *
 * Returns `false` (never throws) for every failure mode:
 * unsupported algorithm, unknown format, malformed signature,
 * mismatch.
 *
 * Example — GitHub webhook style:
 * ```php
 * $rawHeader = $request->getHeaderLine( 'X-Hub-Signature-256' ) ;
 * // 'sha256=abcdef…' — strip the prefix
 * $sig = substr( $rawHeader , strlen( 'sha256=' ) ) ;
 *
 * if ( !verifyHmacSignature( $request->getBody()->getContents() , $sig , $secret ) )
 * {
 *     return new Response( 401 ) ;
 * }
 * ```
 *
 * @param string $payload   The raw request body (bytes, never
 *                          parsed).
 * @param string $signature The signature value to verify, encoded
 *                          as specified by `$format`.
 * @param string $secret    The shared secret.
 * @param string $algo      The hash algorithm — one of the
 *                          {@see HashAlgorithm} constants. Defaults
 *                          to `'sha256'`.
 * @param string $format    One of the {@see SignatureFormat}
 *                          constants — `'hex'`, `'base64'` or
 *                          `'base64url'`. Defaults to `'hex'`.
 *
 * @return bool `true` when the signature is valid.
 */
function verifyHmacSignature
(
    string $payload   ,
    string $signature ,
    string $secret    ,
    string $algo      = HashAlgorithm::SHA256 ,
    string $format    = SignatureFormat::HEX ,
) :bool
{
    if ( $signature === '' || $secret === '' )
    {
        return false ;
    }

    if ( !in_array( $algo , hash_hmac_algos() , true ) )
    {
        return false ;
    }

    $raw = hash_hmac( $algo , $payload , $secret , true ) ;

    $expected = match ( $format )
    {
        SignatureFormat::HEX       => bin2hex( $raw ) ,
        SignatureFormat::BASE64    => base64_encode( $raw ) ,
        SignatureFormat::BASE64URL => base64UrlEncode( $raw ) ,
        default                    => null ,
    } ;

    if ( $expected === null )
    {
        return false ;
    }

    return hash_equals( $expected , $signature ) ;
}
