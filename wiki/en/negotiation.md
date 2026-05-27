# Content negotiation

The `helpers/negotiation/` folder covers `Accept*` negotiation (RFC 7231 §5.3) and `Content-Type` parsing (RFC 7231 §3.1.1.1). Returns use the `AcceptField` and `ContentTypeField` enums to keep magic strings out of consumers.

## Parsing `Accept*` headers

### `parseAcceptHeader( string $header ) : array`

Parses **any** header from the `Accept` family (Accept, Accept-Language, Accept-Encoding — same syntax). Returns a list of entries sorted by q-value descending (stable on the header order).

```php
use function oihana\http\helpers\negotiation\parseAcceptHeader ;

use oihana\http\enums\AcceptField ;

$entries = parseAcceptHeader( 'text/html;q=0.9, application/json' ) ;

foreach ( $entries as $entry )
{
    $type    = $entry[ AcceptField::TYPE    ] ; // 'application/json' then 'text/html'
    $quality = $entry[ AcceptField::QUALITY ] ; // 1.0 then 0.9
    $params  = $entry[ AcceptField::PARAMS  ] ; // [] (other parameters)
}
```

Conventions:
- Types are **lowercased**.
- q is a `float` in `[0.0, 1.0]` (clamped), default `1.0`, fallback `1.0` on non-numeric.
- Quoted values (`"…"`) are unwrapped.
- `q=0` is kept (explicit refusal) and lands last.

### For `Accept-Language` and `Accept-Encoding`

The three headers (`Accept`, `Accept-Language`, `Accept-Encoding`) share the same RFC 7231 §5.3 grammar — `parseAcceptHeader()` is therefore the single entry point. The returned DTO is identical for the three; only the conventional meaning of the `type` field differs (media type, language tag, encoding name).

```php
parseAcceptHeader( 'fr-FR,fr;q=0.9,en;q=0.7' ) ;
// [
//   [ type => 'fr-fr' , quality => 1.0 , params => [] ] ,
//   [ type => 'fr'    , quality => 0.9 , params => [] ] ,
//   [ type => 'en'    , quality => 0.7 , params => [] ] ,
// ]

parseAcceptHeader( 'gzip, deflate, br;q=1.0' ) ;
// [ gzip, deflate, br entries ]
```

For `Accept-Language`: tags are lowercased (RFC 4647 §3.3.1, case-insensitive comparison).

## Best-match selection

### `negotiate( string $accept , array $available , ?string $default = null ) : ?string`

For an `Accept*` header and a list of server-side available values, returns the one that best satisfies the client. Handles wildcards `*`, `*/*`, `type/*`. Skips `q=0` entries. The picked candidate's casing is **preserved** in the return value (`'Application/JSON'` stays `'Application/JSON'`).

```php
use function oihana\http\helpers\negotiation\negotiate ;

// Media negotiation
negotiate
(
    'text/html;q=0.9, application/json' ,
    [ 'application/json' , 'text/html' ] ,
) ;
// 'application/json' (q=1.0 beats q=0.9)

// Language negotiation with wildcard
negotiate
(
    'fr;q=0.9, *;q=0.5' ,
    [ 'en' , 'es' ] ,
    'en' ,
) ;
// 'en' (matched via `*`)

// No match
negotiate
(
    'application/json' ,
    [ 'text/html' ] ,
    'text/html' ,
) ;
// 'text/html' (default fallback)
```

### `matchAcceptPattern( string $pattern , string $candidate ) : bool`

Public sub-helper used by `negotiate`. One-to-one test of a pattern (already parsed, lowercased) against a candidate. Handy when you have already parsed the entries and test candidates one at a time.

```php
matchAcceptPattern( 'text/*'      , 'text/plain' ) ;  // true
matchAcceptPattern( 'text/*'      , 'image/png'  ) ;  // false
matchAcceptPattern( 'text/html'   , 'TEXT/HTML'  ) ;  // true (case-insensitive)
matchAcceptPattern( '*/*'         , 'whatever'   ) ;  // true
```

## Parsing `Content-Type`

### `parseContentType( string $header ) : array`

Parses a `Content-Type` into the tuple `{type, charset, boundary, params}`. Special cases are surfaced under their own keys; every param is also available under `params`.

```php
use function oihana\http\helpers\negotiation\parseContentType ;

use oihana\http\enums\ContentTypeField ;

$parsed = parseContentType( 'text/html; charset=UTF-8' ) ;

$parsed[ ContentTypeField::TYPE     ] ; // 'text/html'
$parsed[ ContentTypeField::CHARSET  ] ; // 'utf-8'  (lowercased)
$parsed[ ContentTypeField::BOUNDARY ] ; // null
$parsed[ ContentTypeField::PARAMS   ] ; // [ 'charset' => 'utf-8' ]

parseContentType( 'multipart/form-data; boundary="---WebKit"' ) ;
// boundary = '---WebKit'  (case preserved — case-sensitive per RFC 2046)
```

Differences vs. PHP native:
- `type` and `charset` lowercased (case-insensitive per RFC).
- `boundary` case preserved (case-sensitive in multipart).
- Quoted values unwrapped.

## Key vocabulary tables

### `AcceptField`

| Constant | Key | Contents |
|---|---|---|
| `TYPE` | `'type'` | media / language / encoding, lowercased |
| `QUALITY` | `'quality'` | `float` in `[0.0, 1.0]` |
| `PARAMS` | `'params'` | other parameters `array<string,string>` |

### `ContentTypeField`

| Constant | Key | Contents |
|---|---|---|
| `TYPE` | `'type'` | media-type, lowercased |
| `CHARSET` | `'charset'` | charset lowercased or `null` |
| `BOUNDARY` | `'boundary'` | multipart boundary (case preserved) or `null` |
| `PARAMS` | `'params'` | all params `array<string,string>` |

## Design choice

`parseAcceptHeader` keeps the header order on q-value ties (**stable** sort) — it's a "raw view" of what the client said. Specificity-based tie-breaking (`text/html` preferred over `*/*` at q=1) is `negotiate`'s job, not the parser's. Deliberate, documented behaviour.
