# HMAC signatures

The `helpers/signatures/` folder covers the two recurring patterns of HTTP signature verification:

1. **Pre-signed URLs** — unguessable, time-bounded links (S3-style downloads, password reset, magic login, temporary share).
2. **Signed webhooks** — Stripe / GitHub / Slack / Mailchimp payload verification.

All comparisons use `hash_equals()` — constant-time, safe against timing-side-channel attacks.

## Signed URLs

### `signUrl( string $url , string $secret , ?int $ttlSeconds = null , string $algo = 'sha256' ) : string`

Signs a URL with an HMAC and appends `?sig=<base64url>` (and `&exp=<timestamp>` when a TTL is supplied).

```php
use function oihana\http\helpers\signatures\signUrl ;

$url = signUrl
(
    'https://api.example.com/files/42?download=1' ,
    $secret ,
    ttlSeconds: 600 ,  // expires in 10 minutes
) ;
// https://api.example.com/files/42?download=1&exp=1767225600&sig=…
```

Characteristics:
- The URL is **canonicalised** before signing via `normalizeUrl()` (query keys sorted, scheme/host lowercased, default port dropped) — the order of query params does not affect the signature.
- The helper is **idempotent**: existing `sig` / `exp` are stripped before re-signing, so re-signing a signed URL works.
- Base64url **without padding** in the `sig` — URL-safe, no `+`, `/`, `=`.
- Throws `InvalidArgumentException` on empty secret / unknown algorithm / unparseable URL (fail fast).

### `verifySignedUrl( string $url , string $secret , string $algo = 'sha256' ) : bool`

Symmetric inverse. Returns `true` when the signature is valid AND (if `exp` is present) the URL has not yet expired.

```php
use function oihana\http\helpers\signatures\verifySignedUrl ;

if ( !verifySignedUrl( $signedUrl , $secret ) )
{
    return new Response( 401 ) ;
}

// …serve the signed resource
```

Fail-closed: **always** returns `false` (never throws) on every failure mode — missing `sig`, expired `exp`, malformed base64url, signature mismatch, unparseable URL. The caller can use the boolean as the **sole** allow/deny gate.

`$algo` must match the one passed to `signUrl()`. The `sha256` default is consistent across both helpers.

### Anti-tampering guarantees

```php
// Signed URL
$signed = signUrl( 'https://api.example.com/file?download=1' , $secret , 600 ) ;
verifySignedUrl( $signed , $secret ) ;             // true

// Query tampering — rejected
$tampered = str_replace( 'download=1' , 'download=0' , $signed ) ;
verifySignedUrl( $tampered , $secret ) ;           // false

// Path tampering — rejected
$tampered = str_replace( '/file' , '/admin' , $signed ) ;
verifySignedUrl( $tampered , $secret ) ;           // false
```

## Webhooks (payload signing)

### `verifyHmacSignature( string $payload , string $signature , string $secret , string $algo = 'sha256' , string $format = 'hex' ) : bool`

Verifies an HMAC against a raw payload — the cryptographic primitive of webhooks.

```php
use function oihana\http\helpers\signatures\verifyHmacSignature ;

$payload = (string) $request->getBody() ;
$header  = $request->getHeaderLine( 'X-Hub-Signature-256' ) ;
// 'sha256=abcdef…' — strip the vendor prefix
$sig = substr( $header , strlen( 'sha256=' ) ) ;

if ( !verifyHmacSignature( $payload , $sig , $secret ) )
{
    return new Response( 401 ) ;
}
```

The `$format` parameter must match the sender's encoding:

| Value | Encoding | Typical vendors |
|---|---|---|
| `'hex'` (default) | lowercased hexadecimal | GitHub, Slack, Mailchimp |
| `'base64'` | standard base64 | Plaid webhooks, some custom |
| `'base64url'` | RFC 4648 §5 base64url | JWT-style, JWS detached |

Not handled here (**intentionally**):
- Stripe `Stripe-Signature: t=…,v1=…` — strip the timestamp and the `v1=` prefix first.
- GitHub `X-Hub-Signature-256: sha256=…` — strip the `sha256=` prefix first.
- Slack `X-Slack-Signature: v0=…` + `X-Slack-Request-Timestamp` — strip `v0=` and concatenate the timestamp into the payload per Slack's recipe.

The helper focuses on the crypto primitive (compute HMAC, compare constant-time). Vendor-specific framing remains the integration's responsibility.

## Typed constants

Avoid the raw strings — the signing vocabulary is exposed as typed enums in `oihana\http\enums`:

- `SignatureFormat::HEX` / `::BASE64` / `::BASE64URL` — the `$format` accepted by `verifyHmacSignature()`.
- `SignedUrlField::SIGNATURE` (`'sig'`) / `::EXPIRY` (`'exp'`) — the query-parameter names written by `signUrl()` and read by `verifySignedUrl()`. Handy when you inspect a signed URL yourself.

```php
use oihana\http\enums\SignatureFormat ;

verifyHmacSignature( $payload , $sig , $secret , 'sha256' , SignatureFormat::BASE64 ) ;
```

## Technical choices

- `hash_equals()` everywhere — constant-time comparisons.
- `base64UrlEncode` / `base64UrlDecode` from `oihana\core\encoding` (`oihana/php-core` package) — URL-safe encoding without padding, strict alphabet validation on read.
- The `sha256` default covers the common need. `sha512` recommended for long-term concerns. `sha1` only tolerated for backward compatibility with legacy webhooks — prefer 256+ for new code.

## See also

- [URL](urls.md) — `signUrl()` uses `normalizeUrl()` internally.
- [Authorization](authorization.md) — for `Bearer` JWT and `Basic`.
