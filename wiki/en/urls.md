# URL / Query string

The `helpers/url/` folder covers the most common URI operations in application code (RFC 3986). Ten helpers in string-in/string-out (or `UriInterface`-in/out for the PSR-7 manipulators).

| Helper | What it does |
|---|---|
| `parseQueryString()` | Parse a query string into a `name → list of values` map (duplicates preserved). |
| `buildQueryString()` | Build a query string back from such a map (exact inverse). |
| `withQueryParams()` | Immutably merge params into a PSR-7 `UriInterface` (`null` removes a key). |
| `removeQueryParam()` | Remove a query key (and all its values) from a PSR-7 URI. |
| `normalizeUrl()` | Canonical form for dedup / cache / compare (lowercase scheme+host, drop default port, sort query). |
| `isAbsoluteUrl()` | Tell whether a string carries a scheme (is an absolute URL). |
| `getHost()` | Extract a URL host, normalised (lowercased, IPv6 brackets stripped), `null` if absent. |
| `isPublicUrl()` | Tell whether a URL targets a publicly reachable host. |
| `isLocalUrl()` | Tell whether a URL targets a local / private / reserved host. |
| `withUrlComponents()` | Derive a URL from another by replacing / removing components. |

## Query string parsing / building

### `parseQueryString( string $query , bool $formEncoded = false ) : array<string, list<string>>`

Parses a query string into a `name → list of values` map. **Differences with PHP's `parse_str()`**:

- Duplicates are **preserved**: `'a=1&a=2'` → `['a' => ['1', '2']]` (instead of overwriting).
- Keys are **opaque**: `'a[]=1&a[]=2'` → `['a[]' => ['1', '2']]` (PHP would interpret `[]` as append syntax).
- Each value is **always an array** — predictable type, no surprise `string|array`.

```php
use function oihana\http\helpers\url\parseQueryString ;

parseQueryString( 'a=1&b=2' ) ;
// [ 'a' => [ '1' ] , 'b' => [ '2' ] ]

parseQueryString( 'tag=php&tag=http' ) ;
// [ 'tag' => [ 'php' , 'http' ] ]

parseQueryString( 'q=hello%20world' ) ;       // RFC 3986 (rawurldecode)
// [ 'q' => [ 'hello world' ] ]

parseQueryString( 'q=hello+world' , true ) ;  // form-encoded (+ → space)
// [ 'q' => [ 'hello world' ] ]
```

### `buildQueryString( array $params , bool $rfc3986 = true ) : string`

Exact inverse. Symmetric with `parseQueryString` — roundtrips cleanly.

```php
use function oihana\http\helpers\url\buildQueryString ;

buildQueryString( [ 'a' => '1' , 'b' => '2' ] ) ;
// 'a=1&b=2'

buildQueryString( [ 'tag' => [ 'php' , 'http' ] ] ) ;
// 'tag=php&tag=http'  (repeated keys, not `tag[0]=php`)

buildQueryString( [ 'q' => 'hello world' ] ) ;
// 'q=hello%20world'  (RFC 3986, default)

buildQueryString( [ 'verbose' => true , 'debug' => null ] ) ;
// 'verbose=1&debug'  (true→1, false→0, null→bare key)
```

## PSR-7 manipulation

### `withQueryParams( UriInterface $uri , array $params ) : UriInterface`

Immutable update of a URI with merged params. Existing keys are replaced, others preserved. `null` removes the key.

```php
use function oihana\http\helpers\url\withQueryParams ;

// $uri = https://example.com/path?a=1
$next = withQueryParams( $uri , [ 'b' => '2' , 'a' => null ] ) ;
// $next  → https://example.com/path?b=2
// $uri   unchanged
```

### `removeQueryParam( UriInterface $uri , string $name ) : UriInterface`

Syntactic sugar for removing a key (with all its values when multi-valued).

```php
use function oihana\http\helpers\url\removeQueryParam ;

removeQueryParam( $uri , 'tag' )  ;
// removes tag (and all its values) from the query
```

## Inspection / canonicalisation

### `normalizeUrl( string $url ) : string`

Canonical form of a URL for deduplication, caching, comparison:
- scheme and host lowercased
- default port dropped (`http:80`, `https:443`, `ws:80`, `wss:443`, `ftp:21`)
- query keys sorted alphabetically (multi-valued keys keep their relative order)
- fragment preserved as-is
- fail-open on unparseable input

```php
use function oihana\http\helpers\url\normalizeUrl ;

normalizeUrl( 'HTTPS://Example.COM:443/Path?b=2&a=1' ) ;
// 'https://example.com/Path?a=1&b=2'
```

**Not applied** (out of scope, would need a heavier URI library):
- percent-decoding of unreserved characters in the path
- dot-segment resolution (`/a/./b/../c` → `/a/c`)
- IDN / Punycode normalisation

### `isAbsoluteUrl( string $url ) : bool`

Strict scheme component detection (RFC 3986 §4.3: `ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )` followed by `:`).

```php
use function oihana\http\helpers\url\isAbsoluteUrl ;

isAbsoluteUrl( 'https://example.com/path' ) ; // true
isAbsoluteUrl( 'mailto:alice@example.com'  ) ; // true
isAbsoluteUrl( '//example.com/path'        ) ; // false (protocol-relative)
isAbsoluteUrl( '/api/v1'                   ) ; // false (path-absolute)
isAbsoluteUrl( 'api/v1'                    ) ; // false (relative)
```

### `getHost( string $url ) : ?string`

Extracts the host of a URL in a form ready for comparison, allow-listing or IP validation — lowercased and with IPv6 brackets removed. Returns `null` when there is no host to speak of.

```php
use function oihana\http\helpers\url\getHost ;

getHost( 'https://API.Example.com/path?x=1' ) ; // 'api.example.com'
getHost( 'http://localhost:8080' )             ; // 'localhost'
getHost( 'http://[2001:db8::1]:443/x' )        ; // '2001:db8::1'  (brackets stripped)
getHost( 'mailto:alice@example.com' )          ; // null  (no authority)
getHost( '/relative/path' )                    ; // null
```

The bracket-stripped IPv6 form is meant for *inspection*, not for reinjection into a URL (a bare `::1` is not a valid authority). URL reassembly stays the job of `normalizeUrl()`, which keeps the brackets it needs.

### `isPublicUrl( string $url ) : bool`

Imagine an installer that needs a public callback URL to register a webhook. If the operator points it at `http://localhost:8080` or `http://192.168.1.10`, the remote service will never be able to reach it — so the CLI should refuse early and ask for an explicit public endpoint (a tunnel like ngrok / cloudflared, a reverse proxy, …). `isPublicUrl()` is exactly that gate: it looks at the **host** of a URL and tells you whether it is reachable from the outside world.

- `localhost` and any `*.localhost` sub-domain → `false`
- IP literals (IPv4 or IPv6) are handed to [`isPublicIp()`](ips.md): every loopback, private (RFC 1918 / RFC 4193) and reserved range → `false`
- any other named host (a FQDN such as `api.example.com`) → `true`
- host-less input (relative path, empty string) → `false`

```php
use function oihana\http\helpers\url\isPublicUrl ;

isPublicUrl( 'https://api.example.com'       ) ; // true
isPublicUrl( 'https://8.8.8.8'               ) ; // true
isPublicUrl( 'http://localhost:8080'         ) ; // false
isPublicUrl( 'http://app.localhost'          ) ; // false (sub-domain)
isPublicUrl( 'http://127.0.0.1'              ) ; // false (loopback)
isPublicUrl( 'http://10.0.0.1'               ) ; // false (RFC 1918)
isPublicUrl( 'http://[::1]'                  ) ; // false (loopback)
isPublicUrl( 'http://[fd00::1]'              ) ; // false (unique local)
isPublicUrl( 'http://[2001:4860:4860::8888]' ) ; // true
isPublicUrl( '/relative/path'                ) ; // false (no host)
```

> **Syntactic heuristic, not an anti-SSRF guard.** No DNS resolution is performed: a FQDN that resolves to a private address (`internal.corp.lan` → `10.x`) is still reported as public. Use it as a *routing hint* ("do I need an explicit public endpoint here?"), not as a security boundary against server-side request forgery.

### `isLocalUrl( string $url ) : bool`

The readable counterpart of `isPublicUrl()` — `true` when the URL targets a local or private host (`localhost`, `*.localhost`, loopback / RFC 1918 / RFC 4193 / reserved IP).

```php
use function oihana\http\helpers\url\isLocalUrl ;

isLocalUrl( 'http://localhost:8080' )   ; // true
isLocalUrl( 'http://127.0.0.1' )        ; // true
isLocalUrl( 'http://[::1]' )            ; // true
isLocalUrl( 'https://api.example.com' ) ; // false
isLocalUrl( '/relative/path' )          ; // false (no host)
```

> Not a strict negation of `isPublicUrl()`: a host-less URL is neither public nor local, so **both** return `false` for it. The presence of a host is required.

## Deriving a URL from another

### `withUrlComponents( string $url , array $overrides ) : string`

Sometimes you have a URL and want *almost* the same one — flip `http` to `https`, swap the host, strip the credentials, drop the fragment. `withUrlComponents()` does exactly that: it parses the URL, applies your overrides, and reassembles it. Keys are the `oihana\enums\http\UrlComponent` constants; a `null` value removes the component.

It is string-in / string-out, so you don't need to instantiate a PSR-7 `UriInterface` (and pull a PSR-7 implementation) just to tweak one part.

```php
use oihana\enums\http\UrlComponent ;
use function oihana\http\helpers\url\withUrlComponents ;

// Switch scheme only
withUrlComponents( 'http://example.com/path' , [ UrlComponent::SCHEME => 'https' ] ) ;
// 'https://example.com/path'

// Replace the password only
withUrlComponents( 'https://user:old@example.com' , [ UrlComponent::PASS => 'new' ] ) ;
// 'https://user:new@example.com'

// Strip the query and the fragment
withUrlComponents( 'https://example.com/p?x=1#frag' , [ UrlComponent::QUERY => null , UrlComponent::FRAGMENT => null ] ) ;
// 'https://example.com/p'
```

**Limits** (deliberately out of scope — same pragmatic stance as `normalizeUrl()`):

- **No percent-encoding.** Values are inserted *verbatim*. A value with reserved characters (a password containing `@` or `:`, a path with a space…) must be encoded by you, or the URL comes out malformed.
- **No well-formedness check.** The result is not re-parsed or validated.
- **IPv6 host keeps its brackets.** To set an IPv6 host, pass it bracketed (`[::1]`). Note the asymmetry with `getHost()`, which *strips* them — its output is for inspection, not to feed back here.
- **Authority-bound components.** `user` / `pass` / `port` only appear when a host is present; removing the host drops them too.
- **Fail-open.** If the URL can't be parsed, it is returned untouched and the overrides are ignored.

> Already holding a PSR-7 `UriInterface`? Prefer its native `withScheme()` / `withUserInfo()` / `withHost()` / … — they handle encoding for you. `withUrlComponents()` is the lightweight string-based alternative when you don't have (or don't want) a URI object.

## Path concatenation

No local helper — use **`oihana\files\path\joinPaths()`** from the `oihana/php-files` package (transitively in our `require`). It already handles every relevant case:
- collapses redundant `/`
- preserves the leading slash
- resolves dot segments (RFC 3986 §6.2.2.3)
- supports schemes (`phar://`, `C:\`, …)

```php
use function oihana\files\path\joinPaths ;

joinPaths( '/api/v1/' , '/users/' , '/123/' ) ;
// '/api/v1/users/123'
```

No need to reimplement — one place in the ecosystem, one behaviour.

## See also

- [Signatures](signatures.md) — `signUrl()` uses `normalizeUrl` internally to make the signature order-insensitive.
