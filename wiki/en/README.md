# oihana/php-http — User guide

![Language](https://img.shields.io/badge/language-English-blue)

`oihana/php-http` is a composable PHP library for HTTP-facing code: real client IP detection behind reverse proxies, GDPR-compliant anonymisation, typed cookie builders and parsers, PSR-7 authentication and request inspection helpers, content negotiation, HTTP dates, URL/query string, HMAC signatures for signed URLs and webhooks, User-Agent parser. PSR-7 compatible, zero magic strings, zero external dependency (outside the oihana ecosystem).

## Audience

PHP developers building an API behind one or more reverse proxies (Cloudflare, nginx, AWS ALB, …) who need to:

- identify the real client IP with full control over the trusted-proxy chain, and truncate for GDPR (IPv4 `/24`, IPv6 `/48`);
- emit secure cookies without forgetting `SameSite`, `Secure`, `HttpOnly`, `Partitioned`;
- validate `Authorization` headers (Bearer, Basic), quickly inspect a request (`wantsJson`, `isAjax`, `isHttpsRequest`);
- negotiate content (`Accept*`, `parseContentType`);
- parse and format HTTP dates (RFC 7231 IMF-fixdate, RFC 850, asctime);
- manipulate URLs and query strings without losing duplicates;
- sign URLs with a TTL and verify HMAC webhooks in constant time;
- parse a User-Agent for analytics routing / bot detection.

## Quick start

```php
use function oihana\http\helpers\auth\getBearerToken                 ;
use function oihana\http\helpers\cookies\buildSetCookieHeader        ;
use function oihana\http\helpers\ips\getClientIp                     ;
use function oihana\http\helpers\request\wantsJson                   ;
use function oihana\http\helpers\signatures\verifyHmacSignature      ;

use oihana\http\enums\CookieOption ;
use oihana\http\enums\SameSite     ;

// 1. Client IP behind your reverse-proxy CIDR
$clientIp = getClientIp( $request , [ '10.0.0.0/8' , '192.168.0.0/16' ] ) ;

// 2. Secure session cookie
$header = buildSetCookieHeader( 'session' , $token , 3600 ,
[
    CookieOption::SECURE      => true               ,
    CookieOption::SAME_SITE   => SameSite::STRICT   ,
    CookieOption::PATH        => '/'                ,
]) ;

// 3. Bearer token + JSON negotiation
$token = getBearerToken( $request ) ;
$wantsJson = wantsJson( $request ) ;

// 4. Stripe / GitHub / Slack webhook
$payload = (string) $request->getBody() ;
$sig     = $request->getHeaderLine( 'X-Webhook-Signature' ) ;
if ( !verifyHmacSignature( $payload , $sig , $webhookSecret ) )
{
    return new Response( 401 ) ;
}
```

## Table of contents

### HTTP building blocks

- **[Getting started](getting-started.md)** — installation, PSR-7 mocking, first examples.
- **[IP detection](ips.md)** — `getClientIp`, GDPR anonymisation (`truncateIpToSlash24/48`, `anonymizeIp`), `Forwarded` parsing (RFC 7239), CIDR matching.
- **[Cookies](cookies.md)** — `buildSetCookieHeader`, `expireSetCookieHeader`, `parseCookieHeader`, `parseSetCookieHeader`, `validateCookie{Name,Value}`, enums `CookieAttribute` / `CookieOption` / `CookiePriority` / `SameSite` / `SetCookieField`.
- **[Authorization](authorization.md)** — `parseAuthorizationHeader`, `getBearerToken`, `getBasicAuth`, enums `AuthorizationField` / `BasicAuthField`.
- **[PSR-7 request helpers](request.md)** — `wantsJson`, `isAjax`, `isHttpsRequest`.
- **[Content negotiation](negotiation.md)** — `parseAcceptHeader` (universal for `Accept`, `Accept-Language`, `Accept-Encoding`), `negotiate`, `parseContentType`, enums `AcceptField` / `ContentTypeField`.
- **[HTTP dates](dates.md)** — `parseHttpDate` (3 RFC 7231 formats), `formatHttpDate` (IMF-fixdate).
- **[URL / Query string](urls.md)** — `parseQueryString`, `buildQueryString`, `withQueryParams`, `removeQueryParam`, `normalizeUrl`, `isAbsoluteUrl`.

### Higher-level

- **[User-Agent](user-agent.md)** — `parseUserAgent`, detect helpers, `isBotUserAgent`, `isMobileUserAgent`, enums `BrowserName` / `OsName` + `UserAgentInfo` DTO (`oihana/php-schema`).
- **[HMAC signatures](signatures.md)** — `signUrl`, `verifySignedUrl`, `verifyHmacSignature` (Stripe / GitHub / Slack / Mailchimp), enums `SignatureFormat` / `SignedUrlField`.

### Cross-cutting

- **[Security](security.md)** — trusted-proxy model, GDPR anonymisation, cookie validation, constant-time HMAC, URL canonicalisation, strict base64url, anti-CRLF parsing, recommended baselines, what's NOT covered.

## Source code

The library code lives under [`src/oihana/http/`](../../src/oihana/http/).

## See also

- [Packagist `oihana/php-http`](https://packagist.org/packages/oihana/php-http) — package page.
- [`oihana/php-auth`](https://github.com/BcommeBois/oihana-php-auth) — Casbin RBAC + JWT/OIDC; consumes the IP and cookie helpers.
- [`oihana/php-enums`](https://github.com/BcommeBois/oihana-php-enums) — typed HTTP constants (`HttpHeader`, `HttpStatusCode`, `AuthScheme`, …).
- [`oihana/php-schema`](https://github.com/BcommeBois/oihana-php-schema) — shared DTOs (`UserAgentInfo`, `Session`, …).
- [`oihana/php-standards`](https://github.com/BcommeBois/oihana-php-standards) — standard date formats (`DateFormat::RFC7231`).
- [`oihana/php-core`](https://github.com/BcommeBois/oihana-php-core) — encoding (`base64UrlEncode` / `base64UrlDecode`).
- [`oihana/php-files`](https://github.com/BcommeBois/oihana-php-files) — `joinPaths()` for URL path concatenation.
