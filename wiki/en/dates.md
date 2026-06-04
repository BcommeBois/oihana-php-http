# HTTP dates

The `helpers/dates/` folder ships two helpers for HTTP headers that carry dates: `Date`, `Last-Modified`, `Expires`, `If-Modified-Since`, `If-Unmodified-Since`, `Retry-After`.

| Helper | What it does |
|---|---|
| `parseHttpDate()` | Parse an HTTP-date header (the three RFC 7231 formats) into a UTC `DateTimeImmutable`; `null` when unparseable. |
| `formatHttpDate()` | Format any `DateTimeInterface` as an IMF-fixdate string (converted to UTC, never mutates its input). |

RFC 7231 §7.1.1.1 lists **three** legal formats:

| Format | Example | Status |
|---|---|---|
| IMF-fixdate (RFC 7231) | `Sun, 06 Nov 1994 08:49:37 GMT` | modern, recommended |
| RFC 850 | `Sunday, 06-Nov-94 08:49:37 GMT` | obsolete but still seen in production |
| asctime | `Sun Nov  6 08:49:37 1994` | obsolete |

All three use **GMT** per spec — not `UTC`, not a numeric offset. Our parser accepts all three on read; the emitter writes IMF-fixdate only.

## `parseHttpDate( ?string $value ) : ?DateTimeImmutable`

Parses an HTTP-date header value into a UTC `DateTimeImmutable`. Returns `null` for `null`, empty or unparseable input.

```php
use function oihana\http\helpers\dates\parseHttpDate ;

$dt = parseHttpDate( 'Thu, 31 Dec 2026 23:59:59 GMT' ) ;
$dt->format( DATE_ATOM ) ;          // '2026-12-31T23:59:59+00:00'
$dt->getTimezone()->getName() ;     // 'UTC'

parseHttpDate( null ) ;             // null
parseHttpDate( 'tomorrow' ) ;       // null (not an HTTP-date)
parseHttpDate( '2026-12-31' ) ;     // null (ISO 8601, not RFC 7231)
```

The parser is **strict** on the wire form: it rejects `UTC` or `+0000` instead of `GMT`, even when numerically equivalent.

## `formatHttpDate( DateTimeInterface $dt ) : string`

Formats any `DateTimeInterface` as the IMF-fixdate string. The input is converted to UTC before formatting (HTTP-dates must always end with `GMT`); the helper never mutates its input.

```php
use function oihana\http\helpers\dates\formatHttpDate ;

$utc = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;
formatHttpDate( $utc ) ;
// 'Thu, 31 Dec 2026 23:59:59 GMT'

// Non-UTC: converted before formatting.
$cest = new DateTimeImmutable( '2026-07-01 00:00:00' , new DateTimeZone( 'Europe/Paris' ) ) ;
formatHttpDate( $cest ) ;
// 'Tue, 30 Jun 2026 22:00:00 GMT'
```

## Roundtrip

The two functions are designed to roundtrip cleanly:

```php
$original = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;
$header   = formatHttpDate( $original ) ;
$reparsed = parseHttpDate( $header ) ;

$original->getTimestamp() === $reparsed->getTimestamp() ;  // true
```

## Why not `gmdate('D, d M Y H:i:s \G\M\T', $ts)`?

Three reasons:

1. `gmdate` produces the right string **only** for a timestamp; it cannot convert a tz-aware `DateTimeInterface`;
2. The read side (`parseHttpDate`) must accept all **three** legal formats — needs a try loop and a strict `createFromFormat` per variant;
3. Centralising the use of `org\common\DateFormat::RFC7231` from `oihana/php-standards` avoids every consumer rewriting the format string by hand.

## See also

- [Cookies](cookies.md) — the `Set-Cookie` `Expires` attribute uses the same IMF-fixdate format (emitted automatically by `buildSetCookieHeader` when the `CookieOption::EXPIRES` option is set).
