# Security

`oihana/php-http` is not a security framework — it's a toolkit of HTTP primitives. But several of those primitives carry secure-by-default decisions worth documenting explicitly, both so consumers know what's protected and what isn't, and so the defaults aren't accidentally weakened.

This page lists the fronts that are covered, the classic pitfalls to avoid, and what's explicitly out of scope.

## Trusted-proxy trust model

Helpers that reconstruct client metadata from HTTP headers (`getClientIp()`, `isHttpsRequest()`) honor `X-Forwarded-For`, `X-Forwarded-Proto`, `X-Real-IP` and `Forwarded` (RFC 7239) **only** when `REMOTE_ADDR` itself sits inside a CIDR **trusted-proxy list**. If the list is empty, or the emitting proxy isn't in it, those headers are ignored and the lib falls back to the direct TCP connection.

```php
use function oihana\http\helpers\ips\getClientIp ;
use function oihana\http\helpers\request\isHttpsRequest ;

// Only proxies in these CIDRs can influence the decision.
$trustedProxies = [ '10.0.0.0/8' , '172.16.0.0/12' , '192.168.0.0/16' ] ;

$clientIp = getClientIp( $request , $trustedProxies ) ;
$secure   = isHttpsRequest( $request , $trustedProxies ) ;
```

**Classic pitfall.** Leaving the list empty behind a load balancer **disables** the `X-Forwarded-For` chain walk — the helper returns the LB's IP instead of the client's. Conversely, listing `0.0.0.0/0` (or not filtering at all) **lets any attacker** inject an arbitrary IP through a forged `X-Forwarded-For`. List the exact CIDRs of your proxies, nothing else.

`getClientIp()` walks the `X-Forwarded-For` chain right-to-left, skipping trusted hops until it finds the first **non-trusted** hop — that's the real client. The logic is documented in detail in [`ips.md`](ips.md).

## GDPR anonymisation

Three helpers to produce a clean anonymised IP for audit logs / observability:

- `truncateIpToSlash24()` — IPv4, masks the last octet (`203.0.113.42` → `203.0.113.0`).
- `truncateIpToSlash48()` — IPv6, masks the last 80 bits. Matches the depth recommended by the German BfDI/BSI for GDPR-friendly server logs.
- `anonymizeIp()` — unified entry point that routes IPv4 → `/24` and IPv6 → `/48`, and passes the rest through untouched.

**Recommendation.** In a logging / audit pipeline, use `anonymizeIp()` as the single entry point — it's the only way to guarantee no non-anonymised IP ever lands in a log file in cleartext.

## Cookie validation

`buildSetCookieHeader()` rejects any attempt to inject dangerous characters into the name or value **before emission**:

- Name: must follow the RFC 6265 / RFC 7230 grammar (ASCII tokens, no separators, no control characters).
- Value: rejects ASCII control characters (including `\r` and `\n`) and `;`.

```php
buildSetCookieHeader( 'session\nInjected: header' , $value , 3600 , […] ) ;
// throws InvalidArgumentException — nothing emitted, the attack never makes it out.
```

The `validateCookieName()` and `validateCookieValue()` helpers are exposed publicly so application code can defensively validate user-supplied data **before** passing it to any cookie builder (not just this lib's).

**Recommendation.** NEVER concatenate user-supplied data into a `Set-Cookie` manually: always go through `buildSetCookieHeader()`, or explicitly call `validateCookieValue()` upfront.

## Constant-time comparisons

All HMAC helpers under `helpers/signatures/` use `hash_equals()` for the final comparison — not `===`, not `strcmp()`. That's the standard defense against **timing side-channel attacks** that let an attacker guess a signature byte-by-byte by measuring response time.

Concretely, in `verifySignedUrl()` and `verifyHmacSignature()`, the comparison between the client-supplied HMAC and the expected one is always constant-time. You don't have to configure anything.

## URL canonicalization before signing

`signUrl()` runs the URL through `normalizeUrl()` **before** computing the signature. The canonicalisation:

- Lowercases the scheme and the host.
- Drops default ports (`:80` for http, `:443` for https, etc.).
- Sorts the query string keys alphabetically (duplicate values keep their relative order).

Consequence: `?a=1&b=2` and `?b=2&a=1` produce **the same signature**. An attacker who reorders the parameters of a signed URL can't invalidate the signature (and symmetrically, a client that re-serialises the query doesn't break verification).

```php
use function oihana\http\helpers\signatures\signUrl ;
use function oihana\http\helpers\signatures\verifySignedUrl ;

$url = signUrl( 'https://api.example.com/files/42/download' , $secret , ttlSeconds: 300 ) ;

// 5-minute window before verifySignedUrl rejects.
if ( !verifySignedUrl( $url , $secret ) )
{
    return new Response( 401 ) ;
}
```

**Out of scope.** `normalizeUrl()` does NOT percent-decode unreserved characters, does NOT resolve dot-segments (`./`, `../`), and does NOT do IDN/Punycode. For those layers, process the URL upstream.

## Strict base64url decoding

Signatures from `signUrl()` / `verifySignedUrl()` encode in **base64url** (RFC 4648 §5 — `-` and `_` instead of `+` and `/`, no padding). Decoding uses `oihana\core\encoding\base64UrlDecode()` from `oihana/php-core`, which **rejects upfront** any character outside the `[A-Za-z0-9_-]` alphabet via a regex check before `base64_decode()` is even called.

Why: avoid silently tolerating URL-unsafe variants (`+` / `/` / whitespace / non-ASCII) that could cause aberrations downstream — for instance an attacker sending a signature with `+` in place of a space to exploit a `parse_str`-style coercion server-side.

## Anti-CRLF in parsing

`parseAcceptHeader()`, `parseContentType()` and `parseForwardedHeader()` use `oihana\core\strings\splitOutsideQuotes()` to tokenise their headers. This function **respects quoted regions**: a `\r\n` or a `,` inside a quoted string is not treated as a separator.

Why: defense-in-depth against a malformed header that manages to slip through a lax PSR-7 implementation. An attacker passing `Accept: foo, "bar\r\nInjected: header", baz` cannot make `Injected: header` appear as a separate entry of the parse result.

## What this lib does NOT cover

A few important HTTP security fronts are **out of scope** for `oihana/php-http`. These concerns belong at the middleware layer:

- **CSRF** — no CSRF helper. Use `slim/csrf` or equivalent at the middleware layer.
- **Rate-limiting** — not covered.
- **Security headers** (`Content-Security-Policy`, `Strict-Transport-Security`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-Content-Type-Options`, etc.) — not covered. A future `oihana/php-http-middleware` package is being considered to host those helpers; internal project tracking.
- **CORS** — same, middleware-level, out of scope for a procedural-helpers lib.
- **JWT / OAuth / OIDC authentication & token issuance** — `parseAuthorizationHeader()`, `getBearerToken()`, `getBasicAuth()` only extract and parse headers. To validate/issue JWTs or orchestrate an OAuth/OIDC flow, see `oihana/php-auth`.

## Recommended baselines

### Session cookie

```php
use function oihana\http\helpers\cookies\buildSetCookieHeader ;
use oihana\http\enums\CookieOption ;
use oihana\http\enums\SameSite ;
use oihana\http\enums\CookiePriority ;

$header = buildSetCookieHeader( 'session' , $token , 3600 ,
[
    CookieOption::SECURE      => true                 , // HTTPS only
    CookieOption::HTTP_ONLY   => true                 , // no JS access
    CookieOption::SAME_SITE   => SameSite::STRICT     , // basic CSRF resistance
    CookieOption::PATH        => '/'                  ,
    CookieOption::PRIORITY    => CookiePriority::HIGH , // eviction resistance
    CookieOption::PARTITIONED => true                  , // CHIPS — cross-site partitioned
]) ;
```

### Signed URL with TTL

```php
use function oihana\http\helpers\signatures\signUrl ;
use function oihana\http\helpers\signatures\verifySignedUrl ;

// Issuing side — short TTL for a private download.
$url = signUrl( 'https://api.example.com/files/42/download' , $secret , ttlSeconds: 300 ) ;

// Validation side — a single boolean is the allow/deny decision.
if ( !verifySignedUrl( $url , $secret ) )
{
    return new Response( 401 ) ;
}
```

### HMAC webhook verification

```php
use function oihana\http\helpers\signatures\verifyHmacSignature ;

$payload   = (string) $request->getBody() ;
$signature = $request->getHeaderLine( 'X-Webhook-Signature' ) ;

if ( !verifyHmacSignature( $payload , $signature , $webhookSecret ) )
{
    return new Response( 401 ) ;
}
```

For Stripe (`Stripe-Signature: t=…,v1=…`), GitHub (`X-Hub-Signature-256: sha256=…`), Slack (`X-Slack-Signature: v0=…`) or Mailchimp: **strip the vendor envelope before** calling `verifyHmacSignature()`. The helper only handles the bare signature, intentionally — no vendor-specific coupling.

## See also

- [`ips.md`](ips.md) — `X-Forwarded-For` walk details and trusted-proxy model.
- [`cookies.md`](cookies.md) — attribute catalog and validation.
- [`signatures.md`](signatures.md) — `signUrl`, `verifySignedUrl`, `verifyHmacSignature` in depth.
- [`request.md`](request.md) — `isHttpsRequest` and its symmetry with `getClientIp`.
