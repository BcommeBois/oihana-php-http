# Oihana PHP Http

![Oihana PHP Http](https://raw.githubusercontent.com/BcommeBois/oihana-php-http/main/assets/images/oihana-php-http-logo-inline-512x160.png)

Composable PHP HTTP toolkit. Part of the **Oihana PHP** ecosystem, this package provides battle-tested helpers for HTTP-facing code: client IP detection against reverse proxies, typed `Set-Cookie` header builders, route pattern utilities and user-agent parsing — all PSR-7 compatible, zero magic strings.

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

- **Detect the real client IP** behind a chain of trusted reverse proxies. Walks `X-Forwarded-For` right-to-left, skips trusted hops via CIDR matching, falls back on `Forwarded` (RFC 7239), `X-Real-IP` and `REMOTE_ADDR`. Validates IPv4 and IPv6, normalizes IPv4-mapped IPv6 (`::ffff:1.2.3.4` → `1.2.3.4`), strips IPv6 zone IDs.
- **Anonymise IPs for GDPR-friendly logging** — `truncateIpToSlash24()` (IPv4 → `/24`, last octet zeroed) and `truncateIpToSlash48()` (IPv6 → `/48`, last 80 bits zeroed), or `anonymizeIp()` for a single entry point that routes to the right helper based on the address family.
- **Build `Set-Cookie` headers** with typed attributes: `HttpOnly`, `Secure`, `SameSite=Strict|Lax|None`, `Domain`, `Path`, `Max-Age`.
- **Expand Slim route patterns** carrying optional bracket segments (`/users[/{id:[0-9]+}]`) into their cartesian product of concrete routes — useful for permission seeding and route-by-route authorization.
- **Translate Slim route patterns into Casbin patterns** by collapsing `{placeholder}` segments into `*`.
- **Read the request User-Agent** string via `getUserAgent()`.

### Under the hood

- A consistent set of typed enums and constants — `CookieAttribute`, `CookieOption`, `SameSite`.
- Pure PSR-7 — no framework lock-in. Works with Slim, Laravel, Symfony HTTP Foundation (via PSR-7 bridge), Hyperf, RoadRunner, etc.
- Strongly-typed enums and constants — no magic strings anywhere.

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

- `oihana/php-core` – core helpers and utilities: `https://github.com/BcommeBois/oihana-php-core`
- `oihana/php-enums` – typed constants & enums: `https://github.com/BcommeBois/oihana-php-enums`
- `oihana/php-files` – file system helpers: `https://github.com/BcommeBois/oihana-php-files`
- `oihana/php-reflect` – reflection and hydration utilities: `https://github.com/BcommeBois/oihana-php-reflect`
- `oihana/php-auth` – Casbin RBAC + JWT/OIDC authorization toolkit: `https://github.com/BcommeBois/oihana-php-auth`
