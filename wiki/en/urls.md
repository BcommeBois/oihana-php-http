# URL / Query string

The `helpers/url/` folder covers the most common URI operations in application code (RFC 3986). Six helpers in string-in/string-out (or `UriInterface`-in/out for the PSR-7 manipulators).

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
