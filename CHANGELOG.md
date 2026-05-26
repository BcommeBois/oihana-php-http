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

- `parseAcceptHeader()` helper under `oihana\http\helpers\negotiation` — parses any `Accept*` HTTP request header (RFC 7231 §5.3) into a list of `{type, quality, params}` entries sorted by q-value descending (stable on ties). Types are lowercased; q is clamped to `[0.0, 1.0]` and defaults to `1.0`; non-numeric q falls back to `1.0`. Quoted parameter values are unwrapped.
- `parseAcceptLanguage()` helper under `oihana\http\helpers\negotiation` — thin wrapper around `parseAcceptHeader()` for `Accept-Language`. Language tags are lowercased per RFC 4647 §3.3.1 (case-insensitive comparison).
- `parseAcceptEncoding()` helper under `oihana\http\helpers\negotiation` — thin wrapper around `parseAcceptHeader()` for `Accept-Encoding`. Encoding names lowercased.
- `negotiate()` helper under `oihana\http\helpers\negotiation` — selects the best server-side value from a list of `$available` choices given a client `Accept*` header. Handles wildcards (`*`, `*/*`, `type/*`), skips `q=0` entries, returns `$default` when no match. Candidate casing is preserved in the returned value.
- `matchAcceptPattern()` helper under `oihana\http\helpers\negotiation` — public sub-helper exposed by `negotiate()` for callers that already have a parsed pattern and want to test a single candidate.
- `parseContentType()` helper under `oihana\http\helpers\negotiation` — parses a `Content-Type` header into `{type, charset, boundary, params}`. `charset` is lowercased (case-insensitive per RFC); `boundary` is case-preserved (case-sensitive per RFC 2046); quoted parameter values are unwrapped.
- `AcceptField` enum class under `oihana\http\enums` — `TYPE`, `QUALITY`, `PARAMS`. Keys exposed by the negotiation parsers so consumers can avoid magic strings.
- `ContentTypeField` enum class under `oihana\http\enums` — `TYPE`, `CHARSET`, `BOUNDARY`, `PARAMS`. Keys exposed by `parseContentType()`.
- `parseHttpDate()` helper under `oihana\http\helpers\dates` — parses the three HTTP-date formats listed by RFC 7231 §7.1.1.1 (IMF-fixdate `Sun, 06 Nov 1994 08:49:37 GMT`, RFC 850 `Sunday, 06-Nov-94 …`, asctime `Sun Nov  6 …`) into a UTC `DateTimeImmutable`. Returns `null` for `null`, empty or unparseable input. Strict: rejects `UTC` / `+0000` suffixes that look right but are not legal HTTP-dates.
- `formatHttpDate()` helper under `oihana\http\helpers\dates` — formats a `DateTimeInterface` as the RFC 7231 IMF-fixdate string used by `Date`, `Last-Modified`, `Expires`, `If-Modified-Since` and friends. The input is converted to UTC before formatting (HTTP-dates always end with `GMT`); the helper never mutates its input. Uses `org\common\DateFormat::RFC7231` from `oihana/php-standards` as the canonical format string. Roundtrips with `parseHttpDate()`.
- `parseUserAgent()` helper under `oihana\http\helpers` — pragmatic, dependency-free regex-based parser that turns a `User-Agent` header string into a structured `xyz\oihana\schema\http\UserAgentInfo` DTO with `browser`, `browserVersion`, `os`, `osVersion`, `deviceType`, `isBot` and `raw` fields. Covers the long-tail of common production traffic (Chrome/Firefox/Safari/Edge/Opera/Vivaldi/IE; Windows/macOS/Linux/Android/iOS/iPadOS/ChromeOS; the most prevalent search-engine and social-media bots). Implemented as a thin orchestrator composing the four detection helpers below. Documents its limits in the docblock — pair with `ua-parser/uap-php` if you need exhaustive coverage.
- `detectUserAgentBot()` helper under `oihana\http\helpers` — boolean bot/crawler/automation classifier. Public so callers needing only the bot signal can skip the full parse.
- `detectUserAgentBrowser()` helper under `oihana\http\helpers` — returns the tuple `[name, version]` where `name` matches a `BrowserName` constant when identified, `null` otherwise. Detection order (Edge/Opera/Vivaldi before Chrome, Safari last) is documented in the helper.
- `detectUserAgentOs()` helper under `oihana\http\helpers` — returns the tuple `[name, version]` where `name` matches an `OsName` constant. Windows NT versions are remapped to their marketing equivalent (`NT 10.0` → `10`, `NT 6.1` → `7`, …), macOS / iOS / iPadOS underscores are normalised to dots.
- `detectUserAgentDeviceType()` helper under `oihana\http\helpers` — returns one of the `DeviceType` constants from the UA tokens + the detected OS.
- `BrowserName` enum class under `oihana\http\enums` — `Chrome`, `Edge`, `Firefox`, `IE`, `Opera`, `Safari`, `Vivaldi`. Vocabulary emitted by `detectUserAgentBrowser()` so callers can compare against constants instead of magic strings.
- `OsName` enum class under `oihana\http\enums` — `Android`, `ChromeOS`, `iOS`, `iPadOS`, `Linux`, `macOS`, `Windows`. Vocabulary emitted by `detectUserAgentOs()`.
- `isBotUserAgent()` helper under `oihana\http\helpers` — thin wrapper around `parseUserAgent()` returning a single boolean. Convenient for rate-limiting, audit-log filtering, paywall heuristics.
- `isMobileUserAgent()` helper under `oihana\http\helpers` — thin wrapper around `parseUserAgent()` returning `true` for both **mobile** and **tablet** form factors (matching the popular convention used by `Mobile_Detect` and similar libraries — the typical "serve a mobile UI?" question groups tablets with phones).
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

- Added `oihana/php-schema: dev-main` to `require` — provides the `xyz\oihana\schema\http\UserAgentInfo` DTO returned by `parseUserAgent()` and the `xyz\oihana\schema\constants\http\DeviceType` constants used by `$deviceType`.
- Added `oihana/php-standards: dev-main` to `require` — used to format cookie `Expires` values via the `org\common\DateFormat::RFC7231` constant (RFC 7231 IMF-fixdate).
- Source code under `src/oihana/http/` (19 PHP files):
  - `helpers/` (16 functions): `casbinRoutePattern`, `expandOptionalSegments`, `getUserAgent`, plus `cookies/` (`buildSetCookieHeader`, `expireSetCookieHeader`) and `ips/` (11 helpers covering X-Forwarded-For chain walking, CIDR matching, IPv4/IPv6 canonicalization, public/private detection and `/24` truncation for GDPR logging).
  - `enums/` (3 typed constant classes): `CookieAttribute`, `CookieOption`, `SameSite`.
- Test suite under `tests/oihana/http/` (16 PHP files): all green under PHPUnit 12 strict mode.
- Bilingual user guides under `wiki/{fr,en}/`: README index, getting-started, ips, cookies, route-patterns.
