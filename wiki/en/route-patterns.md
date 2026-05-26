# Route patterns

The `helpers/` folder ships five helpers for manipulating Slim-style route patterns, at every stage of the lifecycle:

1. **Expansion** of bracketed optional segments into concrete variants.
2. **Conversion** to Casbin patterns (for policy seeding or offline OpenAPI).
3. **Compilation** to a regex with named captures.
4. **Matching** a concrete path against a pattern (args extraction).
5. **Conversion** to Casbin from a live PSR-7 request (the historical Request-aware variant).

## Optional segment expansion

### `expandOptionalSegments( string $pattern ) : array`

Returns an array of concrete variants from a Slim pattern with bracketed optional segments.

```php
use function oihana\http\helpers\expandOptionalSegments ;

expandOptionalSegments( '/users' ) ;
// [ '/users' ]

expandOptionalSegments( '/users[/{id:[0-9]+}]' ) ;
// [ '/users' , '/users/{id:[0-9]+}' ]

expandOptionalSegments( '/users[/{id:[0-9]+}][/{action}]' ) ;
// [
//   '/users' ,
//   '/users/{id:[0-9]+}' ,
//   '/users/{action}' ,
//   '/users/{id:[0-9]+}/{action}' ,
// ]
```

Brackets inside `{...}` (e.g. `[0-9]` inside `{id:[0-9]+}`) are part of a regex character class and are **not** treated as optional segments. The function tracks brace depth to distinguish them.

## Regex compilation

### `slimToRegex( string $pattern ) : string`

Compiles a Slim pattern into a PHP regex with named captures. Foundation of the other matching helpers.

```php
use function oihana\http\helpers\slimToRegex ;

slimToRegex( '/users/{id:[0-9]+}' ) ;
// '/^\/users\/(?P<id>[0-9]+)$/'

slimToRegex( '/users[/{id:[0-9]+}]' ) ;
// '/^\/users(?:\/(?P<id>[0-9]+))?$/'

slimToRegex( '/ip/{ip:[0-9]{1,3}\.[0-9]{1,3}}' ) ;
// '/^\/ip\/(?P<ip>[0-9]{1,3}\.[0-9]{1,3})$/'
```

Covers:
- bare placeholders (`{id}` → `(?P<id>[^\/]+)`)
- regex constraints (`{id:[0-9]+}`)
- optional segments (`[...]` → `(?:...)?`)
- `{1,3}` quantifiers inside constraints (brace-depth tracking)
- automatic escaping of the user-written `/` inside a constraint

Throws `InvalidArgumentException` on an unmatched `{`.

## Path matching

### `matchSlimPattern( string $pattern , string $path ) : ?array`

Wraps `slimToRegex` + `preg_match`. Returns the captured args as `array<string, string>`, or `null` if no match. Optional placeholders that didn't match are **omitted** from the result (use `isset()` to test presence).

```php
use function oihana\http\helpers\matchSlimPattern ;

matchSlimPattern( '/users/{id:[0-9]+}' , '/users/42' ) ;
// [ 'id' => '42' ]

matchSlimPattern( '/users/{id:[0-9]+}' , '/users/abc' ) ;
// null (constraint failed)

matchSlimPattern( '/users[/{id:[0-9]+}]' , '/users' ) ;
// [] (matched, no args captured)

matchSlimPattern( '/users[/{id:[0-9]+}]' , '/users/42' ) ;
// [ 'id' => '42' ]
```

Use cases:
- permission seeding from a route table without a live request (Casbin policy generation, `AuthRoutesDumpCommand`-style tooling, OpenAPI)
- unit-testing Slim patterns in isolation
- lightweight routing in non-Slim entry points

## Casbin conversion

### `slimToCasbinPattern( string $pattern ) : string`

**Pure-string** version of `casbinRoutePattern`. Replaces every `{name}` / `{name:regex}` with `:name`. Optional `[...]` are **preserved** — compose with `expandOptionalSegments` to get one Casbin entry per concrete variant.

```php
use function oihana\http\helpers\slimToCasbinPattern ;
use function oihana\http\helpers\expandOptionalSegments ;

slimToCasbinPattern( '/users/{id:[0-9]+}' ) ;
// '/users/:id'

slimToCasbinPattern( '/users[/{id}]' ) ;
// '/users[/:id]'  (brackets preserved)

// Seeding workflow: expansion then conversion
foreach ( expandOptionalSegments( '/users[/{id:[0-9]+}]' ) as $variant )
{
    $canonical = slimToCasbinPattern( $variant ) ;
    // '/users' then '/users/:id'
}
```

### `casbinRoutePattern( ServerRequestInterface $request ) : string`

Historical **Request-aware** variant: inspects the live Slim route via `RouteContext::fromRequest()` and substitutes each path segment that matches a captured argument value with `:name`. Handy in a middleware where you already hold the request.

```php
use function oihana\http\helpers\casbinRoutePattern ;

// GET /users/42 (Slim pattern: /users/{id:[0-9]+})
casbinRoutePattern( $request ) ; // '/users/:id'

// DELETE /policies/75459030 (Slim pattern: /policies[/{id:[0-9]+}])
casbinRoutePattern( $request ) ; // '/policies/:id'
```

Falls back to the raw path when no Slim route is attached.

## Summary: which one to use?

| Need | Helper |
|---|---|
| From a **live** Slim request | `casbinRoutePattern` |
| From a **pattern string** | `slimToCasbinPattern` |
| **List** all variants of a pattern with optionals | `expandOptionalSegments` |
| **Compile** to a regex for custom matching | `slimToRegex` |
| **Match** a path and extract args | `matchSlimPattern` |

## See also

- [Getting started](getting-started.md) — full Casbin seeding example.
