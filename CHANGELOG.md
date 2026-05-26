# Changelog

All notable changes to **oihana/php-http** are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- README: align the "What you can do" section with the helpers actually shipped — drop the IPv6 mention from `truncateIpToSlash24` (currently IPv4-only), drop `Expires` from the cookie attributes list (only `Max-Age` is emitted today), and replace the structured user-agent claim with the actual `getUserAgent()` raw-string accessor. Full IPv6 truncation, cookie `Expires` and structured UA parsing are tracked for upcoming releases.

### Added

- Initial scaffold: Composer manifest, PHPUnit 12 + phpDocumentor 3 configuration, MPL-2.0 license, README, CHANGELOG, sibling-aligned folder layout (`src/`, `tests/`, `wiki/`, `assets/`).
- Source code under `src/oihana/http/` (19 PHP files):
  - `helpers/` (16 functions): `casbinRoutePattern`, `expandOptionalSegments`, `getUserAgent`, plus `cookies/` (`buildSetCookieHeader`, `expireSetCookieHeader`) and `ips/` (11 helpers covering X-Forwarded-For chain walking, CIDR matching, IPv4/IPv6 canonicalization, public/private detection and `/24` truncation for GDPR logging).
  - `enums/` (3 typed constant classes): `CookieAttribute`, `CookieOption`, `SameSite`.
- Test suite under `tests/oihana/http/` (16 PHP files): all green under PHPUnit 12 strict mode.
- Bilingual user guides under `wiki/{fr,en}/`: README index, getting-started, ips, cookies, route-patterns.
