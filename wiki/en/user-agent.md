# User-Agent

The `helpers/` folder ships a regex-based, dependency-free User-Agent parser that turns a `User-Agent` string into a structured DTO. Designed for the long-tail of 95% of typical web traffic (Chrome, Firefox, Safari, Edge, Opera, Vivaldi, IE; Windows, macOS, Linux, Android, iOS, iPadOS, ChromeOS; the major bots).

For exhaustive long-tail coverage (regional bots, rare browsers, full device fingerprinting), plug `ua-parser/uap-php` on top — this lib is deliberately dependency-free.

| Helper | What it does |
|---|---|
| `getUserAgent()` | Read the raw `User-Agent` string from `$_SERVER` (`null` when missing). |
| `parseUserAgent()` | Parse a UA string into a structured `UserAgentInfo` DTO (always returns an instance). |
| `isBotUserAgent()` | Tell whether a UA is a bot / crawler / HTTP tool. |
| `isMobileUserAgent()` | Tell whether a UA is a mobile **or** tablet form factor. |
| *4 low-level detectors* | One signal at a time (`detectUserAgentBot` / `Browser` / `Os` / `DeviceType`) — see [Low-level detectors](#low-level-detectors). |

## Raw read

### `getUserAgent() : ?string`

Reads the `User-Agent` string from `$_SERVER`. Returns `null` when the header is missing.

```php
use function oihana\http\helpers\getUserAgent ;

$raw = getUserAgent() ;
// 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) ...'
```

## Structured parsing

### `parseUserAgent( ?string $ua ) : UserAgentInfo`

Orchestrator. Returns an `xyz\oihana\schema\http\UserAgentInfo` (a DTO from the `oihana/php-schema` package) with the fields `browser`, `browserVersion`, `os`, `osVersion`, `deviceType`, `isBot`, `raw`. Always returns an instance — never `null` — missing fields are exposed as `null` inside the DTO.

```php
use function oihana\http\helpers\parseUserAgent ;

$info = parseUserAgent
(
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) '
    . 'AppleWebKit/605.1.15 (KHTML, like Gecko) '
    . 'Version/17.5 Safari/605.1.15'
) ;

$info->browser        ; // 'Safari'
$info->browserVersion ; // '17.5'
$info->os             ; // 'macOS'
$info->osVersion      ; // '14.5'
$info->deviceType     ; // 'desktop'
$info->isBot          ; // false
$info->raw            ; // UA string preserved verbatim (audit/forensics)
```

## Low-level detectors

The four detectors are **public** — consumers needing only one signal can skip the full parse.

| Helper | Signature | Returns |
|---|---|---|
| `detectUserAgentBot( string $ua )` | bool | `true` if bot / crawler / HTTP tool |
| `detectUserAgentBrowser( string $ua )` | `array{0:?string,1:?string}` | `[name, version]`, `name` = `BrowserName` constant or `null` |
| `detectUserAgentOs( string $ua )` | `array{0:?string,1:?string}` | `[name, version]`, `name` = `OsName` constant or `null` |
| `detectUserAgentDeviceType( string $ua , ?string $os )` | string | `DeviceType` constant |

## Predicate helpers

### `isBotUserAgent( ?string $ua ) : bool`

Thin wrapper around `parseUserAgent`. Accepts `null` or empty (returns `false`). Handy for rate-limiting, audit-log filtering, soft paywalls.

```php
use function oihana\http\helpers\isBotUserAgent ;

if ( isBotUserAgent( $request->getHeaderLine( 'User-Agent' ) ) )
{
    return new Response( 429 ) ;
}
```

### `isMobileUserAgent( ?string $ua ) : bool`

Returns `true` for both **mobile and tablet** form factors — popular convention (`Mobile_Detect` and friends): the typical "should I serve a mobile UI?" question groups tablets with phones.

If you specifically need phones only, use `parseUserAgent($ua)->deviceType === DeviceType::MOBILE`.

## Emitted vocabularies

### `oihana\http\enums\BrowserName`

| Constant | Value | Covers |
|---|---|---|
| `CHROME` | `'Chrome'` | Chrome + Chromium-based without brand token |
| `EDGE` | `'Edge'` | Microsoft Edge (`Edg/`, `EdgA/`, `EdgiOS/`) |
| `FIREFOX` | `'Firefox'` | Firefox + Firefox iOS (`FxiOS/`) |
| `IE` | `'IE'` | Legacy `MSIE` / `Trident` |
| `OPERA` | `'Opera'` | `OPR/` + legacy Presto |
| `SAFARI` | `'Safari'` | Safari (detected last, fallback on `Safari/`) |
| `VIVALDI` | `'Vivaldi'` | Vivaldi |

### `oihana\http\enums\OsName`

| Constant | Value | Covers |
|---|---|---|
| `ANDROID` | `'Android'` | `Android <version>` |
| `CHROME_OS` | `'ChromeOS'` | `CrOS` |
| `IOS` | `'iOS'` | iPhone, iPod |
| `IPADOS` | `'iPadOS'` | iPad (reported separately since iPadOS 13) |
| `LINUX` | `'Linux'` | GNU/Linux distros (Android filtered before) |
| `MACOS` | `'macOS'` | `Mac OS X` + bare `Macintosh` |
| `WINDOWS` | `'Windows'` | `Windows NT <ver>` remapped to marketing version (`10`, `8.1`, `7`, …) |

### `xyz\oihana\schema\constants\http\DeviceType`

Coarse device classification. Constants: `BOT`, `DESKTOP`, `MOBILE`, `TABLET`, `UNKNOWN`. Stable vocabulary defined in the schema package so other tools (audit, sessions, analytics) can consume it.

## `UserAgentInfo` DTO

Lives in `oihana/php-schema` under `xyz\oihana\schema\http\UserAgentInfo` (extends `Intangible` from Schema.org). Handy for embedding inside a `Session` or `AuditAction` record that wants to keep the structured UA breakdown alongside the raw string.

## Documented limit

Recent iPadOS reports a macOS-like UA with a `Macintosh` token and **no** `iPad` hint — those iPads will be classified as `desktop`. Client-Hints (`Sec-CH-UA`, `Sec-CH-UA-Mobile`) are the only reliable signal for that disambiguation.
