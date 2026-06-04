# IP detection

The `helpers/ips/` folder contains 13 helpers that solve the recurring problem of identifying the **real** client IP behind a chain of reverse proxies, plus GDPR-compliant anonymisation. The flagship helper is `getClientIp()`; the others are reusable building blocks.

| Helper | What it does |
|---|---|
| `getClientIp()` | Flagship — resolve the real client IP behind trusted proxies (right-to-left forwarded-chain walk). |
| `anonymizeIp()` | GDPR — route IPv4 to `/24`, IPv6 to `/48`; non-IP passes through. |
| `truncateIpToSlash24()` | Anonymise an IPv4 to `/24` (no-op on IPv6 / invalid). |
| `truncateIpToSlash48()` | Anonymise an IPv6 to `/48` (no-op on IPv4 / invalid). |
| *9 building blocks* | Lower-level pieces (`isPublicIp`, `ipMatchesCidr`, `canonicalIp`, `walkForwardedChain`, …) — see [Building blocks](#building-blocks). |

## Trust model

`X-Forwarded-For` and its cousins are **client-controllable** if you don't filter them. The lib's model is:

1. **You declare your trusted proxies** as a list of CIDR ranges (or single IPs).
2. The lib walks the *forwarded* chain **right-to-left**, skipping every hop that comes from a trusted IP.
3. The first untrusted IP encountered is treated as the real client.
4. If the entire chain is trusted (or empty), the lib falls back to `REMOTE_ADDR`.

This avoids the classic spoofing where a malicious client sends `X-Forwarded-For: 1.2.3.4` to impersonate that IP.

## `getClientIp( ?ServerRequestInterface $request = null , array $trustedProxies = [] , bool $allowPrivate = true , bool $useForwarded = false ) : ?string`

Entry point. Walks the *forwarded* chain, falls back to `Forwarded` (RFC 7239, opt-in), `X-Real-IP`, then `REMOTE_ADDR`. Returns a canonicalised IPv4 or IPv6 string, or `null` when no usable IP is found.

```php
use function oihana\http\helpers\ips\getClientIp ;

$ip = getClientIp( $request ,
[
    '10.0.0.0/8'       , // RFC 1918 private
    '172.16.0.0/12'    ,
    '192.168.0.0/16'   ,
    '103.21.244.0/22'  , // example: a Cloudflare range
]) ;

// Strict public-IP filter
$publicIp = getClientIp( $request , $trustedProxies , allowPrivate: false ) ;

// Enable RFC 7239 Forwarded: parsing
$ip = getClientIp( $request , $trustedProxies , useForwarded: true ) ;
```

## GDPR anonymisation

Three helpers to anonymise before logging:

### `truncateIpToSlash24( ?string $ip ) : ?string`

Truncates an IPv4 to `/24` (last octet zeroed). **IPv4 only** — IPv6 and invalid inputs pass through unchanged (no-op).

```php
use function oihana\http\helpers\ips\truncateIpToSlash24 ;

truncateIpToSlash24( '203.0.113.42' ) ; // '203.0.113.0'
truncateIpToSlash24( '2001:db8::1' ) ;  // '2001:db8::1' (no-op IPv6)
truncateIpToSlash24( null ) ;           // null
```

### `truncateIpToSlash48( ?string $ip ) : ?string`

IPv6 counterpart: truncates to `/48` (last 80 bits zeroed). Recommended by BfDI/BSI for IPv6 logs (functional equivalent of the `/24` IPv4 policy). **IPv6 only** — IPv4 and invalid pass through.

```php
use function oihana\http\helpers\ips\truncateIpToSlash48 ;

truncateIpToSlash48( '2001:db8:cafe:1234::1' ) ; // '2001:db8:cafe::'
truncateIpToSlash48( '203.0.113.42' ) ;          // '203.0.113.42' (no-op IPv4)
```

### `anonymizeIp( ?string $ip ) : ?string`

Unified entry point: routes IPv4 to `/24` and IPv6 to `/48`. Anything not a valid IP passes through.

```php
use function oihana\http\helpers\ips\anonymizeIp ;

$auditedIp = anonymizeIp( getClientIp( $request , $trustedProxies ) ) ;

// IPv4 :  203.0.113.7                              -> 203.0.113.0
// IPv6 :  2001:db8:cafe:1234:5678:9abc:def0:1111   -> 2001:db8:cafe::
// null  : null                                     -> null
```

## Building blocks

| Helper | What it does |
|---|---|
| `walkForwardedChain( array $chain , array $trustedProxies ) : ?string` | Right-to-left walk of an already-parsed chain. Returns the rightmost untrusted IP. |
| `parseForwardedHeader( string $value ) : array` | Parses an [RFC 7239](https://www.rfc-editor.org/rfc/rfc7239) `Forwarded:` header into a list of `for=` values. |
| `extractIpCandidatesFromRequest( ServerRequestInterface $request , bool $useForwarded ) : array` | Reads `X-Forwarded-For`, `Forwarded`, `X-Real-IP`, `CF-Connecting-IP`, etc. from the PSR-7 request. |
| `extractIpCandidatesFromGlobals( bool $useForwarded ) : array` | Same but reads directly from `$_SERVER`. |
| `canonicalIp( string $ip ) : ?string` | Normalises an IP string: lowercases IPv6, strips zone IDs, collapses IPv6-mapped-IPv4. |
| `ipMatchesCidr( string $ip , string $cidr ) : bool` | Match against a CIDR. IPv4 and IPv6. A bare IP without `/n` matches exactly. |
| `ipInList( string $ip , array $cidrs ) : bool` | Vectorised `ipMatchesCidr`. |
| `isPublicIp( string $ip ) : bool` | True if the IP is in a globally routable range. |
| `acceptIp( ?string $ip , bool $allowPrivate ) : ?string` | Validates an IP and returns its canonical form, or `null`. Filters out private/reserved if `$allowPrivate = false`. |

## Mocking pattern in tests

```php
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\ips\getClientIp ;

$request = ( new ServerRequestFactory() )
    ->createServerRequest( 'GET' , '/' , [ 'REMOTE_ADDR' => '10.0.0.1' ] )
    ->withHeader( 'X-Forwarded-For' , '203.0.113.7, 10.0.0.1' ) ;

$ip = getClientIp( $request , [ '10.0.0.0/8' ] ) ;
// '203.0.113.7' — real client beyond the proxy hop
```

The same pattern is used in the lib's IP tests under `tests/oihana/http/helpers/ips/`.

## See also

- [Request helpers](request.md) — `isHttpsRequest()` shares the same trusted-proxy model.
