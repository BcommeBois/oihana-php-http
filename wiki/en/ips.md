# IP detection

The `helpers/ips/` folder contains 11 helpers that solve the recurring problem of identifying the **real** client IP behind a chain of reverse proxies. The flagship helper is `getClientIp()`; the other ten are reusable building blocks.

## Trust model

`X-Forwarded-For` and friends are **client-controllable** if you do not filter them. The library's model is:

1. **You declare your trusted proxies** as a list of CIDRs (or single IPs).
2. The library walks the forwarded chain **right-to-left**, skipping every hop that comes from a trusted IP.
3. The first non-trusted IP encountered is treated as the real client.
4. If the whole chain is trusted (or empty), the library falls back on `REMOTE_ADDR`.

This avoids the classic spoofing where a malicious client sends `X-Forwarded-For: 1.2.3.4` to impersonate that IP.

## `getClientIp( Request $request , array $trustedProxies = [] ) : ?string`

Entry point. Walks the forwarded chain, falls back on `Forwarded` (RFC 7239), `X-Real-IP`, then `REMOTE_ADDR`. Returns a canonicalized IPv4 or IPv6 string, or `null` if no usable IP was found.

```php
use function oihana\http\helpers\ips\getClientIp ;

$ip = getClientIp( $request ,
[
    '10.0.0.0/8'       , // private RFC 1918
    '172.16.0.0/12'    ,
    '192.168.0.0/16'   ,
    '103.21.244.0/22'  , // example: a Cloudflare range
]) ;
```

## Building blocks

| Helper | What it does |
|---|---|
| `walkForwardedChain( array $chain , array $trustedProxies ) : ?string` | Right-to-left walk of an already-parsed chain. Returns the rightmost non-trusted IP. |
| `parseForwardedHeader( string $value ) : array` | Parses an [RFC 7239](https://www.rfc-editor.org/rfc/rfc7239) `Forwarded:` header (e.g. `for="[2001:db8::1]:80";proto=https`) into a list of `for=` values. |
| `extractIpCandidatesFromRequest( Request $request ) : array` | Reads `X-Forwarded-For`, `Forwarded`, `X-Real-IP` from the request and returns the candidates in order. |
| `extractIpCandidatesFromGlobals( array $server = null ) : array` | Same as above but reading directly from `$_SERVER` — useful from middleware bootstrapping that does not yet have a PSR-7 request. |
| `canonicalIp( string $ip ) : ?string` | Normalizes an IP string: lowercases IPv6, strips zone IDs (`fe80::1%eth0` → `fe80::1`), collapses IPv4-mapped IPv6 (`::ffff:1.2.3.4` → `1.2.3.4`). Returns `null` if the string is not a valid IP. |
| `ipMatchesCidr( string $ip , string $cidr ) : bool` | Boolean match against a CIDR. Supports both IPv4 (`10.0.0.0/8`) and IPv6 (`2001:db8::/32`). A bare IP without `/n` matches exactly. |
| `ipInList( string $ip , array $cidrs ) : bool` | Vectorized `ipMatchesCidr` against a list. |
| `isPublicIp( string $ip ) : bool` | True if the IP is in a globally-routable range (not RFC 1918 private, not loopback, not link-local, not multicast). |
| `acceptIp( string $ip , array $allowList , array $denyList = [] ) : bool` | Composite gate: an IP is accepted if it matches the allow list AND is not in the deny list. Empty allow list = accept everything. |
| `truncateIpToSlash24( ?string $ip ) : ?string` | GDPR-friendly anonymization: zeroes the last octet of an IPv4 and the last 80 bits of an IPv6 (effectively a `/48` truncation). Returns `null` on `null` input. |

## GDPR anonymization

For audit logs, security alerts and analytics, **truncate before persistence**:

```php
use function oihana\http\helpers\ips\truncateIpToSlash24 ;

$auditedIp = truncateIpToSlash24( getClientIp( $request , $trustedProxies ) ) ;

// IPv4:  203.0.113.7  -> 203.0.113.0
// IPv6:  2001:db8:abc:def:1234:5678:90ab:cdef  -> 2001:db8:abc::
// null:  null         -> null  (no exception)
```

This is enough to bucket users by city/ISP without storing personal data.

## Test mocking pattern

The 11 helpers are pure functions of their inputs. Testing them with `Slim\Psr7\Factory\ServerRequestFactory` is straightforward:

```php
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\ips\getClientIp ;

$factory = new ServerRequestFactory() ;
$request = $factory->createServerRequest( 'GET' , '/' )
                   ->withHeader( 'X-Forwarded-For' , '203.0.113.7, 10.0.0.1' ) ;

$server = $_SERVER ;
$_SERVER[ 'REMOTE_ADDR' ] = '10.0.0.1' ;        // trusted reverse proxy

$ip = getClientIp( $request , [ '10.0.0.0/8' ] ) ;
$this->assertSame( '203.0.113.7' , $ip ) ;       // real client past the proxy hop

$_SERVER = $server ;
```

The same pattern is used in the library's own 109 IP tests under `tests/oihana/http/helpers/ips/`.
