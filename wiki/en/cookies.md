# Cookies

The `helpers/cookies/` folder contains two helpers to build standards-compliant `Set-Cookie` headers without juggling string concatenation. All attributes go through three typed enums — no magic strings, no forgotten flags.

## `buildSetCookieHeader( string $name , string $value , array $attributes = [] ) : string`

Builds a single `Set-Cookie` header value from a `name`, a `value`, and a map of attributes.

```php
use function oihana\http\helpers\cookies\buildSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SameSite        ;

$header = buildSetCookieHeader( 'session' , 'abc123' ,
[
    CookieAttribute::HTTP_ONLY => true              ,
    CookieAttribute::SECURE    => true              ,
    CookieAttribute::SAME_SITE => SameSite::STRICT  ,
    CookieAttribute::PATH      => '/'               ,
    CookieAttribute::DOMAIN    => 'example.com'     ,
    CookieAttribute::MAX_AGE   => 3600              ,
    CookieAttribute::EXPIRES   => 'Wed, 09 Jun 2026 10:18:14 GMT' ,
]) ;

// session=abc123; Domain=example.com; Path=/; Expires=Wed, 09 Jun 2026 10:18:14 GMT;
//   Max-Age=3600; HttpOnly; Secure; SameSite=Strict
```

The value is **not URL-encoded** automatically — encode it yourself if it contains reserved characters. Attributes are emitted in the canonical order regardless of the order in the input array.

## `expireSetCookieHeader( string $name , array $attributes = [] ) : string`

Emits a deletion `Set-Cookie` for the given name: `Max-Age=0` + `Expires=` in the past. Reuse the same `Path` / `Domain` you used to set the cookie, otherwise the browser will not match the deletion.

```php
use function oihana\http\helpers\cookies\expireSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;

$header = expireSetCookieHeader( 'session' ,
[
    CookieAttribute::PATH   => '/'           ,
    CookieAttribute::DOMAIN => 'example.com' ,
]) ;

// session=; Domain=example.com; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0
```

## `CookieAttribute` enum

Typed constants for every cookie attribute supported by the spec.

| Constant | Header attribute | Value type |
|---|---|---|
| `CookieAttribute::DOMAIN` | `Domain` | `string` |
| `CookieAttribute::PATH` | `Path` | `string` |
| `CookieAttribute::EXPIRES` | `Expires` | RFC 1123 date string |
| `CookieAttribute::MAX_AGE` | `Max-Age` | `int` (seconds) |
| `CookieAttribute::HTTP_ONLY` | `HttpOnly` | `bool` (no value emitted, presence only) |
| `CookieAttribute::SECURE` | `Secure` | `bool` |
| `CookieAttribute::SAME_SITE` | `SameSite` | `SameSite::*` value |
| `CookieAttribute::PRIORITY` | `Priority` | `string` (`Low` / `Medium` / `High`) |
| `CookieAttribute::PARTITIONED` | `Partitioned` | `bool` |

## `SameSite` enum

| Constant | Header value | Meaning |
|---|---|---|
| `SameSite::STRICT` | `Strict` | Cookie sent **only** on same-site requests — toughest, breaks cross-site links into authenticated pages. |
| `SameSite::LAX` | `Lax` | Cookie sent on same-site requests + top-level GET navigation. The browser default since 2020. |
| `SameSite::NONE` | `None` | Cookie sent on all cross-site requests. **Requires `Secure=true`** — browsers reject `SameSite=None` over plain HTTP. |

## Defensive defaults

For session cookies behind HTTPS, the recommended baseline is:

```php
CookieAttribute::HTTP_ONLY => true              ,
CookieAttribute::SECURE    => true              ,
CookieAttribute::SAME_SITE => SameSite::STRICT  ,
CookieAttribute::PATH      => '/'               ,
```

Drop `SameSite` to `LAX` only if you need cookies to survive top-level GET navigations from external sites (common for SSO callback flows).
