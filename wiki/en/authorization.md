# Authorization

The `helpers/auth/` folder covers parsing of the `Authorization` header (RFC 7235) and the `Bearer` (RFC 6750) and `Basic` (RFC 7617) schemes.

## Low-level parser

### `parseAuthorizationHeader( string $header ) : ?array`

Parses the `Authorization` (or `Proxy-Authorization`) header into a `{scheme, credentials}` tuple. The scheme is normalised to the canonical casing carried by `oihana\enums\http\AuthScheme` (from `oihana/php-enums`) — `'BEARER'`, `'bearer'`, `'BeArEr'` → all remapped to `'Bearer'`. Unknown schemes pass through as-is. Credentials are returned verbatim (Digest-style comma-separated parameters kept intact).

```php
use function oihana\http\helpers\auth\parseAuthorizationHeader ;

use oihana\http\enums\AuthorizationField ;
use oihana\enums\http\AuthScheme ;

$parsed = parseAuthorizationHeader( 'Bearer eyJhbGci.eyJzdWIi.signed' ) ;

$parsed[ AuthorizationField::SCHEME      ] ; // AuthScheme::BEARER = 'Bearer'
$parsed[ AuthorizationField::CREDENTIALS ] ; // 'eyJhbGci.eyJzdWIi.signed'

parseAuthorizationHeader( '' ) ;             // null
```

## PSR-7 helpers

### `getBearerToken( ServerRequestInterface $request ) : ?string`

Extracts the Bearer token from a PSR-7 request. Returns `null` when the header is missing, the scheme is not `Bearer`, or the credentials are empty.

```php
use function oihana\http\helpers\auth\getBearerToken ;

$token = getBearerToken( $request ) ;

if ( $token === null )
{
    return new Response( 401 ) ;
}

$claims = $jwt->decode( $token ) ;
```

### `getBasicAuth( ServerRequestInterface $request ) : ?array`

Extracts the username/password pair from the Basic scheme (RFC 7617). Decodes the base64, splits on the **first** `:` (the password may contain colons, RFC 7617 §2). Returns `null` on invalid base64 or missing `:`.

```php
use function oihana\http\helpers\auth\getBasicAuth ;
use oihana\http\enums\BasicAuthField ;

$creds = getBasicAuth( $request ) ;

if ( $creds === null )
{
    return new Response( 401 ) ;
}

$user = $creds[ BasicAuthField::USER ] ;
$pass = $creds[ BasicAuthField::PASS ] ;

// hash_equals for constant-time comparison
if ( $user !== $expectedUser
     || !hash_equals( $expectedHash , password_hash( $pass , PASSWORD_DEFAULT ) ) )
{
    return new Response( 401 ) ;
}
```

Legal edge cases:
- Username and/or password can be **empty** — RFC 7617 allows it. `base64('user:')` returns `[user => 'user', pass => '']`.
- The password may contain additional colons (`base64('user:my:complex:password')` → `pass = 'my:complex:password'`).

## Vocabularies (enums)

### `AuthorizationField`

| Constant | Key | Contents |
|---|---|---|
| `SCHEME` | `'scheme'` | scheme normalised via `AuthScheme` when recognised |
| `CREDENTIALS` | `'credentials'` | everything after the first whitespace (verbatim) |

### `BasicAuthField`

| Constant | Key | Contents |
|---|---|---|
| `USER` | `'user'` | part before the first `:` |
| `PASS` | `'pass'` | part after the first `:` |

### `AuthScheme` (from `oihana/php-enums`)

IANA-registered schemes: `BASIC`, `BEARER`, `DIGEST`, `HOBA`, `MUTUAL`, `NEGOTIATE`, `OAUTH`, `SCRAM_SHA_1`, `SCRAM_SHA_256`, `VAPID`. Reused here for the case-insensitive comparison.

## Vendor-specific schemes

The parser **does not unwrap** vendor-specific envelopes (`'sha256=<sig>'` from GitHub, `'t=…,v1=…'` from Stripe, `'v0=…:…'` from Slack…). For those formats:
- For HMAC-signed webhooks, see [Signatures](signatures.md) — the `verifyHmacSignature()` helper deals with the signature payload itself; strip the envelope first.
- For Digest, OAuth1.0a, etc., parse the returned `credentials` into `key=value` pairs in your own integration code.

## See also

- [Request helpers](request.md) — `wantsJson()`, `isAjax()`, `isHttpsRequest()` for other request inspection.
- [HMAC signatures](signatures.md) — webhook verification and signed URLs.
