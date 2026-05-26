# Cookies

The `helpers/cookies/` folder contains six helpers to build and parse `Set-Cookie` / `Cookie` headers without juggling string concatenation. All attributes go through typed enums — zero magic strings, zero forgotten flags.

## Builder

### `buildSetCookieHeader( string $name , ?string $value , int $maxAge , array $options = [] ) : string`

Builds a `Set-Cookie` header. The `$name` and `$value` are validated on input — invalid inputs → `InvalidArgumentException` (security against CRLF / response-splitting / attribute-injection attacks).

```php
use function oihana\http\helpers\cookies\buildSetCookieHeader ;

use oihana\http\enums\CookieOption ;
use oihana\http\enums\CookiePriority ;
use oihana\http\enums\SameSite ;

$header = buildSetCookieHeader
(
    'session' ,         // name
    $token ,            // value (or null)
    3600 ,              // max-age in seconds
    [
        CookieOption::SECURE      => true                   ,
        CookieOption::SAME_SITE   => SameSite::STRICT       ,
        CookieOption::PATH        => '/'                    ,
        CookieOption::DOMAIN      => 'example.com'          ,
        CookieOption::EXPIRES     => new DateTimeImmutable( '+1 hour' , new DateTimeZone( 'UTC' ) ) ,
        CookieOption::PRIORITY    => CookiePriority::HIGH   ,
        CookieOption::PARTITIONED => true                   ,
    ]
) ;
// session=…; Path=/; Max-Age=3600; SameSite=Strict; HttpOnly; Secure;
//   Domain=example.com; Expires=Thu, 31 Dec 2026 …GMT; Priority=High; Partitioned
```

Applied defaults (override via `$options`): `Path=/`, `HttpOnly`, `SameSite=Lax`, no `Domain`, no `Secure`, no `Expires`, no `Priority`, no `Partitioned`.

#### Accepted formats for `Expires`

| Type passed | Behaviour |
|---|---|
| `int` | Unix timestamp, formatted in UTC as IMF-fixdate (RFC 7231) |
| `string` | passed as-is (escape hatch for exotic formats) |
| `DateTimeInterface` | converted to UTC, formatted as IMF-fixdate |
| `null` or absent | the `Expires` attribute is not emitted |

#### Input validation

- **Name**: strict RFC 7230 `token` grammar (letters, digits, `! # $ % & ' * + - . ^ _ \` | ~`). Whitespace, separators (`( ) < > @ , ; : \ " / [ ] ? = { }`) and controls → exception.
- **Value**: rejects ASCII control characters (0x00–0x1F + 0x7F) and `;` which would break parsing. Whitespace, `"`, `,`, `\` are tolerated for interop (technically not RFC 6265 strict). Empty value accepted (used by `expireSetCookieHeader`).

### `expireSetCookieHeader( string $name , array $options = [] ) : string`

Emits a deletion `Set-Cookie`: `value=''` + `Max-Age=0`. Reuse the same `Path` / `Domain` / `SameSite` you used to set the cookie, otherwise the browser will not match the deletion.

```php
use function oihana\http\helpers\cookies\expireSetCookieHeader ;

use oihana\http\enums\CookieOption ;

$header = expireSetCookieHeader
(
    'session' ,
    [
        CookieOption::PATH   => '/'           ,
        CookieOption::DOMAIN => 'example.com' ,
        CookieOption::SECURE => true          ,
    ]
) ;
// session=; Path=/; Max-Age=0; SameSite=Lax; HttpOnly; Secure; Domain=example.com
```

## Parsers

### `parseCookieHeader( string $header ) : array<string, string>`

Parses a `Cookie:` request header value into a `name → value` map. Read-side counterpart of `buildSetCookieHeader`.

```php
use function oihana\http\helpers\cookies\parseCookieHeader ;

parseCookieHeader( 'PHPSESSID=abc; user=jane' ) ;
// [ 'PHPSESSID' => 'abc' , 'user' => 'jane' ]
```

- Splits on the **first** `=` (values may legitimately contain `=`, e.g. base64 padding).
- Values returned **verbatim**, no automatic URL-decoding (do it yourself if needed).
- On duplicate, **last occurrence wins** — like `$_COOKIE`.

### `parseSetCookieHeader( string $header ) : array`

Parses a full `Set-Cookie` line into a `{name, value, attributes}` tuple. Handy in tests to assert the structure of a generated header, or to inspect a cookie set by an upstream service.

```php
use function oihana\http\helpers\cookies\parseSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SetCookieField ;

$parsed = parseSetCookieHeader( 'access_token=abc; Path=/; Max-Age=3600; HttpOnly' ) ;

$parsed[ SetCookieField::NAME       ] ; // 'access_token'
$parsed[ SetCookieField::VALUE      ] ; // 'abc'
$parsed[ SetCookieField::ATTRIBUTES ] ;
// [
//   'Path'     => '/' ,
//   'Max-Age'  => '3600' ,
//   'HttpOnly' => true ,
// ]
```

Attribute names are normalised to their canonical casing via the `CookieAttribute` lookup (e.g. `path=/` from the wire becomes `Path=/` in the output).

## Standalone validation

### `validateCookieName( string $name ) : void`
### `validateCookieValue( string $value ) : void`

Publicly exposed so application code can validate user-supplied input **before** injecting it into a cookie. Throw `InvalidArgumentException` on invalid input.

## Vocabularies (enums)

### `CookieOption` — keys of the `$options` array

| Constant | Key | Typical value |
|---|---|---|
| `DOMAIN` | `'domain'` | `string`, empty to skip |
| `EXPIRES` | `'expires'` | `int\|string\|DateTimeInterface\|null` |
| `HTTP_ONLY` | `'httpOnly'` | `bool`, default `true` |
| `PARTITIONED` | `'partitioned'` | `bool`, default `false` |
| `PATH` | `'path'` | `string`, default `'/'` |
| `PRIORITY` | `'priority'` | `CookiePriority` constant or `null` |
| `SAME_SITE` | `'sameSite'` | `SameSite` constant, default `LAX` |
| `SECURE` | `'secure'` | `bool`, default `false` |

### `CookieAttribute` — wire-format names (read side)

| Constant | Wire | Notes |
|---|---|---|
| `DOMAIN` | `Domain` | |
| `EXPIRES` | `Expires` | IMF-fixdate RFC 7231 |
| `HTTP_ONLY` | `HttpOnly` | flag |
| `MAX_AGE` | `Max-Age` | seconds |
| `PARTITIONED` | `Partitioned` | flag (CHIPS) |
| `PATH` | `Path` | |
| `PRIORITY` | `Priority` | `CookiePriority` |
| `SAME_SITE` | `SameSite` | `SameSite::*` |
| `SECURE` | `Secure` | flag |

### `SameSite`

| Constant | Value | Meaning |
|---|---|---|
| `STRICT` | `Strict` | sent **only** on same-site requests — the strictest, breaks cross-site links to authenticated pages |
| `LAX` | `Lax` | same-site + top-level GET navigation. Browser default since 2020. |
| `NONE` | `None` | all cross-site requests. **Requires `Secure=true`** |

### `CookiePriority`

| Constant | Value | Eviction policy |
|---|---|---|
| `LOW` | `Low` | evicted first under quota pressure |
| `MEDIUM` | `Medium` | default when the attribute is absent |
| `HIGH` | `High` | evicted last — reserve for critical session cookies |

### `SetCookieField` — keys of the tuple returned by `parseSetCookieHeader`

| Constant | Key | Contents |
|---|---|---|
| `NAME` | `'name'` | cookie name |
| `VALUE` | `'value'` | value (verbatim) |
| `ATTRIBUTES` | `'attributes'` | attribute map (`array<string, string\|true>`) |

## Defensive baseline

For a session cookie behind HTTPS, the recommended recipe:

```php
buildSetCookieHeader( 'session' , $token , 3600 ,
[
    CookieOption::SECURE    => true              ,
    CookieOption::SAME_SITE => SameSite::STRICT  ,
    CookieOption::PATH      => '/'               ,
])
```

`HttpOnly` is `true` by default. Use `SameSite::LAX` only when the cookie needs to survive top-level GET navigations from external sites (typical for SSO callbacks).
