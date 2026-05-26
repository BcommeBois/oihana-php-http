# Changelog

All notable changes to **oihana/php-http** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- `buildSetCookieHeader()` now validates `$name` and `$value` and throws `InvalidArgumentException` when either contains forbidden characters (control characters, `;`, or — for names — any non-token character per RFC 7230). Empty values remain accepted (used by `expireSetCookieHeader()`). **Behavioural change**: calls that previously emitted malformed `Set-Cookie` headers will now fail loud at construction time. This is the intended hardening against CRLF / response-splitting and attribute-injection attacks.
- README: rework the GDPR truncation bullet to mention both `truncateIpToSlash24()` (IPv4) and `truncateIpToSlash48()` (IPv6) plus the unified `anonymizeIp()` entry point, now that all three are shipped.
- README: align the "What you can do" section with the helpers actually shipped — drop the IPv6 mention from `truncateIpToSlash24` (currently IPv4-only), drop `Expires` from the cookie attributes list (only `Max-Age` is emitted today), and replace the structured user-agent claim with the actual `getUserAgent()` raw-string accessor. Full IPv6 truncation, cookie `Expires` and structured UA parsing are tracked for upcoming releases.

### Added

- `truncateIpToSlash48()` helper under `oihana\http\helpers\ips` — IPv6 counterpart of `truncateIpToSlash24()`, keeps the first 48 bits of the address (the network prefix) and zeroes the last 80 bits. Matches the GDPR-friendly anonymisation depth recommended by the German BfDI/BSI for IPv6 server logs. Non-IPv6 input is returned untouched, matching the no-op contract of the IPv4 helper.
- `anonymizeIp()` helper under `oihana\http\helpers\ips` — unified single entry point that routes valid IPv4 to `truncateIpToSlash24()` (`/24`), valid IPv6 to `truncateIpToSlash48()` (`/48`), and passes everything else through untouched. Use in logging / audit pipelines that want a single anonymisation depth across both address families.
- `buildSetCookieHeader()` now emits three additional `Set-Cookie` attributes when the corresponding options are set: `Expires` (`CookieOption::EXPIRES`, accepts `int` Unix timestamp / `string` pre-formatted / `DateTimeInterface` / `null`), `Priority` (`CookieOption::PRIORITY`, one of the `CookiePriority` constants or `null`) and `Partitioned` (`CookieOption::PARTITIONED`, bool flag — CHIPS). All three are appended at the end of the header so the existing emission order is preserved when none of them are set. `int` and `DateTimeInterface` `Expires` values are normalised to UTC and formatted as RFC 7231 IMF-fixdate (e.g. `Thu, 31 Dec 2026 23:59:59 GMT`). Unknown `Priority` values throw `InvalidArgumentException`.
- `CookiePriority` enum class under `oihana\http\enums` with `LOW`, `MEDIUM`, `HIGH` constants matching the Chromium cookie-priority extension to RFC 6265.
- `CookieAttribute::EXPIRES`, `::PRIORITY`, `::PARTITIONED` wire-format constants.
- `CookieOption::EXPIRES`, `::PRIORITY`, `::PARTITIONED` option keys.
- `parseCookieHeader()` helper — parses an HTTP request `Cookie:` header into a `name => value` map. Reciprocal read-side counterpart of `buildSetCookieHeader()`. Values are returned verbatim (no URL-decoding).
- `parseSetCookieHeader()` helper — parses a single `Set-Cookie` response header into a structured `{name, value, attributes}` tuple. Attribute names are normalised to canonical casing via `CookieAttribute` lookups. Useful for tests and for inspecting cookies set by upstream services.
- `SetCookieField` enum class under `oihana\http\enums` — exposes the `NAME`, `VALUE`, `ATTRIBUTES` keys of the tuple returned by `parseSetCookieHeader()` so consumers can avoid magic strings.
- `validateCookieName()` and `validateCookieValue()` helpers under `oihana\http\helpers\cookies` — enforce the RFC 6265 / RFC 7230 grammar for cookie names and reject HTTP-injection-prone values (ASCII control characters and `;`). Used internally by `buildSetCookieHeader()` and exposed publicly so application code can validate user-supplied data defensively.
- Initial scaffold: Composer manifest, PHPUnit 12 + phpDocumentor 3 configuration, MPL-2.0 license, README, CHANGELOG, sibling-aligned folder layout (`src/`, `tests/`, `wiki/`, `assets/`).

### Dependencies

- Added `oihana/php-standards: dev-main` to `require` — used to format cookie `Expires` values via the `org\common\DateFormat::RFC7231` constant (RFC 7231 IMF-fixdate).
- Source code under `src/oihana/http/` (19 PHP files):
  - `helpers/` (16 functions): `casbinRoutePattern`, `expandOptionalSegments`, `getUserAgent`, plus `cookies/` (`buildSetCookieHeader`, `expireSetCookieHeader`) and `ips/` (11 helpers covering X-Forwarded-For chain walking, CIDR matching, IPv4/IPv6 canonicalization, public/private detection and `/24` truncation for GDPR logging).
  - `enums/` (3 typed constant classes): `CookieAttribute`, `CookieOption`, `SameSite`.
- Test suite under `tests/oihana/http/` (16 PHP files): all green under PHPUnit 12 strict mode.
- Bilingual user guides under `wiki/{fr,en}/`: README index, getting-started, ips, cookies, route-patterns.
