# Oihana PHP Http

![Oihana PHP Http](https://raw.githubusercontent.com/BcommeBois/oihana-php-http/main/assets/images/oihana-php-http-logo-inline-512x160.png)

Composable PHP HTTP toolkit. Part of the **Oihana PHP** ecosystem, this package provides battle-tested helpers for HTTP-facing code: client IP detection against reverse proxies, GDPR-compliant anonymisation, typed `Set-Cookie` builders and parsers, PSR-7 authentication and request inspection helpers, content negotiation, HTTP dates, URL/query string toolkit, HMAC signatures for signed URLs and webhooks, User-Agent parser. PSR-7 compatible, zero magic strings.

[![Latest Version](https://img.shields.io/packagist/v/oihana/php-http.svg?style=flat-square)](https://packagist.org/packages/oihana/php-http)
[![Total Downloads](https://img.shields.io/packagist/dt/oihana/php-http.svg?style=flat-square)](https://packagist.org/packages/oihana/php-http)
[![License](https://img.shields.io/packagist/l/oihana/php-http.svg?style=flat-square)](LICENSE)

## 📚 Documentation

Full API reference (generated with phpDocumentor): `https://bcommebois.github.io/oihana-php-http`

User guides (FR + EN) live under [`wiki/`](wiki/).

## 📦 Installation

Requires [PHP 8.4+](https://php.net/releases/). Install via [Composer](https://getcomposer.org/):

```bash
composer require oihana/php-http
```

## ✨ What you can do

### Network & IP

- **Detect the real client IP** behind a chain of trusted reverse proxies. Walks `X-Forwarded-For` right-to-left, skips trusted hops via CIDR matching, falls back on `Forwarded` (RFC 7239), `X-Real-IP` and `REMOTE_ADDR`. Validates IPv4 and IPv6, normalizes IPv4-mapped IPv6, strips IPv6 zone IDs.
- **Anonymise IPs for GDPR-friendly logging** — `truncateIpToSlash24()` (IPv4 → `/24`), `truncateIpToSlash48()` (IPv6 → `/48`, BfDI/BSI recommendation), or `anonymizeIp()` for a single entry point routing by address family.

### Cookies

- **Build & parse `Set-Cookie` / `Cookie` headers** with strict validation (rejects CRLF injection, control characters, malformed names). Typed attributes: `HttpOnly`, `Secure`, `SameSite=Strict|Lax|None`, `Domain`, `Path`, `Max-Age`, `Expires` (RFC 7231 IMF-fixdate), `Priority` (Low|Medium|High), `Partitioned` (CHIPS).

### Auth & PSR-7 request inspection

- **Authorization helpers** — `parseAuthorizationHeader()`, `getBearerToken()`, `getBasicAuth()` (RFC 7617 split on first colon).
- **Request inspection** — `wantsJson()`, `isAjax()`, `isHttpsRequest()` (with anti-spoofing trusted-proxy filter symmetric with `getClientIp()`).

### Content negotiation

- **`Accept*` parser** — `parseAcceptHeader()` is a single universal parser for `Accept`, `Accept-Language` and `Accept-Encoding` (same RFC 7231 §5.3 grammar). Returns entries sorted by q-value, stable on ties.
- **`negotiate()`** — best-match selection with wildcard support (`*`, `*/*`, `type/*`), case-preserving candidate return.
- **`parseContentType()`** — `{type, charset, boundary, params}` tuple, case-insensitive on type/charset, case-preserving on boundary.

### Dates, URLs, signatures

- **HTTP dates** — `parseHttpDate()` accepts all three RFC 7231 §7.1.1.1 formats (IMF-fixdate, RFC 850, asctime); `formatHttpDate()` emits IMF-fixdate from any `DateTimeInterface` converted to UTC.
- **URL & query string toolkit** — `parseQueryString()` / `buildQueryString()` preserve duplicates, `withQueryParams()` / `removeQueryParam()` immutable PSR-7 updates, `normalizeUrl()` canonical form, `isAbsoluteUrl()`, `getHost()` normalised host extraction, `isPublicUrl()` / `isLocalUrl()` (local / private / reserved host detection), `withUrlComponents()` derive a URL by replacing/removing components.
- **HMAC signatures** — `signUrl()` / `verifySignedUrl()` for pre-signed URLs with TTL; `verifyHmacSignature()` for webhook payload verification (Stripe / GitHub / Slack / Mailchimp).

### User-Agent

- **Pragmatic, dependency-free parser** — `parseUserAgent()` returns a structured `UserAgentInfo` DTO (browser, OS, device class, bot flag). Predicates `isBotUserAgent()` and `isMobileUserAgent()` for the common one-shot questions.

### Under the hood

- Consistent typed enums and constants — `CookieAttribute`, `CookieOption`, `CookiePriority`, `SameSite`, `SetCookieField`, `AcceptField`, `ContentTypeField`, `AuthorizationField`, `BasicAuthField`, `BrowserName`, `OsName`, `SignatureFormat`, `SignedUrlField`.
- Pure PSR-7 — no framework lock-in. Works with Slim, Laravel, Symfony HTTP Foundation (via PSR-7 bridge), Hyperf, RoadRunner, etc.
- Strongly-typed enums and constants — no magic strings anywhere.
- Constant-time HMAC comparisons (`hash_equals()`) — safe against timing-side-channel attacks.

## ✅ Running tests

Run all tests:

```bash
composer test
```

Run a specific test file:

```bash
composer test ./tests/oihana/http/helpers/ips/GetClientIpTest.php
```

## 🛠️ Generate the documentation

We use [phpDocumentor](https://phpdoc.org/) to generate documentation into the `./docs` folder.

```bash
composer doc
```

## 🧾 License

Licensed under the [Mozilla Public License 2.0 (MPL‑2.0)](https://www.mozilla.org/en-US/MPL/2.0/).

## 👤 About the author

- Author: Marc ALCARAZ (aka eKameleon)
- Email: `marc@ooop.fr`
- Website: `https://www.ooop.fr`

## 🔗 Related packages

- `oihana/php-core` – core helpers and utilities (`base64UrlEncode` / `base64UrlDecode` consumed by `signUrl`): `https://github.com/BcommeBois/oihana-php-core`
- `oihana/php-enums` – typed constants & enums (`HttpHeader`, `AuthScheme`, …): `https://github.com/BcommeBois/oihana-php-enums`
- `oihana/php-files` – file system helpers (`joinPaths` for URL path concatenation): `https://github.com/BcommeBois/oihana-php-files`
- `oihana/php-reflect` – reflection and hydration utilities (`ConstantsTrait` powering every enum class): `https://github.com/BcommeBois/oihana-php-reflect`
- `oihana/php-schema` – shared DTOs (`UserAgentInfo` returned by `parseUserAgent`, `Session`, …): `https://github.com/BcommeBois/oihana-php-schema`
- `oihana/php-standards` – standard date formats (`DateFormat::RFC7231` used by `formatHttpDate` and the cookie `Expires` attribute): `https://github.com/BcommeBois/oihana-php-standards`
- `oihana/php-auth` – Casbin RBAC + JWT/OIDC authorization toolkit, consumer of the IP and cookie helpers: `https://github.com/BcommeBois/oihana-php-auth`
