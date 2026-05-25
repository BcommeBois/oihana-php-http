# Route patterns

Two small but recurring problems when working with Slim-style route patterns:

1. A pattern carrying **optional bracket segments** (`/users[/{id:[0-9]+}]`) registers a single Slim route serving multiple URL shapes. Tools that need a 1:1 mapping between a route row and something else (a seeded permission, a Casbin policy, an auto-generated OpenAPI path) must expand each optional into its concrete variants first.
2. **Translating a Slim pattern to a Casbin policy pattern** requires collapsing every `{placeholder}` into `*` (Casbin's wildcard) while preserving the path structure.

`oihana/php-http` ships one helper for each.

## `expandOptionalSegments( string $pattern ) : array`

Returns an array of concrete pattern variants from a Slim pattern carrying optional bracket segments.

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

### Brackets inside `{...}` are NOT optional groups

`[0-9]` inside `{id:[0-9]+}` is part of a regex character class — Slim parses it as the constraint on the `id` placeholder. The implementation tracks brace depth and **does not** treat brackets inside `{...}` as optional segments.

```php
expandOptionalSegments( '/items/{key:[A-Z]{2,4}}' ) ;
// [ '/items/{key:[A-Z]{2,4}}' ]   // no expansion, the brackets are inside {...}
```

### Practical use

Permission seeding in `oihana/php-auth` calls this helper before mapping each concrete pattern to a Casbin policy. So does the integration test command `AuthTestServiceProbeCommand` when it walks Slim's route registry to check that every advertised verb-path pair has a permission.

## `casbinRoutePattern( string $slimPattern ) : string`

Translates a Slim route pattern (after optional-segment expansion) into a Casbin policy pattern by collapsing `{placeholder}` into `*`.

```php
use function oihana\http\helpers\casbinRoutePattern ;

casbinRoutePattern( '/users/{id}' ) ;
// '/users/*'

casbinRoutePattern( '/users/{id}/sessions/{sid}' ) ;
// '/users/*/sessions/*'

casbinRoutePattern( '/products' ) ;
// '/products'  (no placeholders, returned as-is)

casbinRoutePattern( '/items/{key:[A-Z]{2,4}}' ) ;
// '/items/*'  (constraint stripped along with the placeholder)
```

### Use with Casbin matchers

In a Casbin policy file, the resulting pattern is matched with `keyMatch2` or `globMatch`:

```ini
[matchers]
m = keyMatch2( r.obj , p.obj ) && r.act == p.act
```

```csv
p, role:editor, /users/*,             GET
p, role:editor, /users/*/sessions/*,  GET
```

## Combining the two

```php
use function oihana\http\helpers\expandOptionalSegments ;
use function oihana\http\helpers\casbinRoutePattern ;

$slim = '/users[/{id}[/{action}]]' ;
foreach ( expandOptionalSegments( $slim ) as $concrete )
{
    echo $concrete . '  →  ' . casbinRoutePattern( $concrete ) . PHP_EOL ;
}

// /users                    →  /users
// /users/{id}               →  /users/*
// /users/{id}/{action}      →  /users/*/*
// /users/{action}           →  /users/*
```

Note the legitimate duplicate `/users/*` — when two concrete variants collapse to the same Casbin pattern, deduplicate before seeding.
