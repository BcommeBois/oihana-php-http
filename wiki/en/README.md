# oihana/php-http — User guide

![Language](https://img.shields.io/badge/language-English-blue)

`oihana/php-http` is a small PHP toolkit for HTTP-facing code: client IP detection against reverse proxies, typed `Set-Cookie` header builders, route pattern utilities, and user-agent parsing. PSR-7 compatible, zero magic strings.

## Who this documentation is for

PHP developers building an API behind one or more reverse proxies (Cloudflare, nginx, AWS ALB, …) who need to:

- correctly identify the real client IP, with full control over the trusted-proxy chain;
- truncate IPs to `/24` (IPv4) or `/48` (IPv6) for GDPR-compliant logging;
- emit `Set-Cookie` headers without juggling string concatenation and forgetting `SameSite` or `HttpOnly`;
- handle Slim route patterns with optional bracket segments — for permission seeding, route-by-route authorization, or 1:1 route → Casbin policy mapping.

## Quick start

```php
use function oihana\http\helpers\ips\getClientIp ;
use function oihana\http\helpers\cookies\buildSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SameSite        ;

// 1. Detect the real client IP, trusting only your reverse proxy CIDR(s)
$clientIp = getClientIp( $request , [ '10.0.0.0/8' , '192.168.0.0/16' ] ) ;

// 2. Build a Set-Cookie header for an authenticated session
$header = buildSetCookieHeader( 'session' , $token ,
[
    CookieAttribute::HTTP_ONLY => true ,
    CookieAttribute::SECURE    => true ,
    CookieAttribute::SAME_SITE => SameSite::STRICT ,
    CookieAttribute::PATH      => '/' ,
    CookieAttribute::MAX_AGE   => 3600 ,
]) ;
```

## Table of contents

- [Getting started](getting-started.md) — install, PSR-7 request mocking, first two-liners.
- [IP detection (ips/)](ips.md) — `getClientIp`, `walkForwardedChain`, `parseForwardedHeader`, `canonicalIp`, `ipMatchesCidr`, `ipInList`, `isPublicIp`, `acceptIp`, `truncateIpToSlash24`, `extractIpCandidatesFrom*`.
- [Cookies](cookies.md) — `buildSetCookieHeader`, `expireSetCookieHeader`, typed `CookieAttribute` / `CookieOption` / `SameSite` enums.
- [Route patterns](route-patterns.md) — `expandOptionalSegments` (Slim optional segments → cartesian product), `casbinRoutePattern` (Slim `{placeholder}` → Casbin `*`).

## Source code

The framework code lives under [`src/oihana/http/`](../../src/oihana/http/).

## See also

- [Packagist `oihana/php-http`](https://packagist.org/packages/oihana/php-http) — the package page.
- [`oihana/php-auth`](https://github.com/BcommeBois/oihana-php-auth) — Casbin RBAC + JWT/OIDC, consumes the route pattern helpers when seeding permissions.
- [`oihana/php-enums`](https://github.com/BcommeBois/oihana-php-enums) — typed HTTP constants (`HttpHeader`, `HttpStatusCode`, …) used throughout the examples.
