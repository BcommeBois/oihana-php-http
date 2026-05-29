<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Output encodings accepted by the HMAC signature helpers.
 *
 * The format describes how the raw HMAC bytes are encoded by the
 * sender before they travel over the wire — it must match exactly
 * what the remote service emits:
 *
 * - `hex`       — lowercased hexadecimal, the most common choice
 *                 (GitHub `X-Hub-Signature-256`, Slack
 *                 `X-Slack-Signature`, Mailchimp, …).
 * - `base64`    — standard base64 with `+` / `/` and `=` padding.
 * - `base64url` — RFC 4648 §5 base64url, `-` / `_` without padding.
 *
 * @see \oihana\http\helpers\signatures\verifyHmacSignature()
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class SignatureFormat
{
    use ConstantsTrait ;

    /**
     * Lowercased hexadecimal encoding (`hex`).
     */
    public const string HEX = 'hex' ;

    /**
     * Standard base64 with `+` / `/` and `=` padding (`base64`).
     */
    public const string BASE64 = 'base64' ;

    /**
     * RFC 4648 §5 base64url, `-` / `_` without padding (`base64url`).
     */
    public const string BASE64URL = 'base64url' ;
}
