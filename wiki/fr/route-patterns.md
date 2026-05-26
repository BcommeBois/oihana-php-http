# Motifs de route

Le dossier `helpers/` contient cinq helpers pour manipuler les motifs de route style Slim, à toutes les étapes du cycle de vie :

1. **Expansion** des segments optionnels entre crochets en variantes concrètes.
2. **Conversion** vers les motifs Casbin (pour le *seeding* de policy ou OpenAPI offline).
3. **Compilation** en regex avec captures nommées.
4. **Matching** d'un path concret contre un motif (extraction des args).
5. **Conversion** vers Casbin depuis une requête PSR-7 vivante (la version Request-aware historique).

## Expansion des optionnels

### `expandOptionalSegments( string $pattern ) : array`

Retourne un tableau de variantes concrètes à partir d'un motif Slim portant des segments optionnels entre crochets.

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

Les crochets dans `{...}` (par ex. `[0-9]` dans `{id:[0-9]+}`) font partie d'une classe de caractères regex et **ne sont pas** traités comme des segments optionnels. La fonction suit la profondeur des accolades pour les distinguer.

## Compilation en regex

### `slimToRegex( string $pattern ) : string`

Compile un motif Slim en regex PHP avec captures nommées. Foundation des autres helpers de matching.

```php
use function oihana\http\helpers\slimToRegex ;

slimToRegex( '/users/{id:[0-9]+}' ) ;
// '/^\/users\/(?P<id>[0-9]+)$/'

slimToRegex( '/users[/{id:[0-9]+}]' ) ;
// '/^\/users(?:\/(?P<id>[0-9]+))?$/'

slimToRegex( '/ip/{ip:[0-9]{1,3}\.[0-9]{1,3}}' ) ;
// '/^\/ip\/(?P<ip>[0-9]{1,3}\.[0-9]{1,3})$/'
```

Couvre :
- placeholders simples (`{id}` → `(?P<id>[^\/]+)`)
- contraintes regex (`{id:[0-9]+}`)
- segments optionnels (`[...]` → `(?:...)?`)
- quantifiers `{1,3}` dans les contraintes (brace-depth tracking)
- échappement automatique du `/` que l'utilisateur écrit dans une contrainte sans le quoter

Throws `InvalidArgumentException` sur un `{` non fermé.

## Matching de path

### `matchSlimPattern( string $pattern , string $path ) : ?array`

Wrap `slimToRegex` + `preg_match`. Retourne les args capturés en `array<string, string>`, ou `null` si pas de match. Les placeholders optionnels non matchés sont **omis** du résultat (utilisez `isset()` pour tester la présence).

```php
use function oihana\http\helpers\matchSlimPattern ;

matchSlimPattern( '/users/{id:[0-9]+}' , '/users/42' ) ;
// [ 'id' => '42' ]

matchSlimPattern( '/users/{id:[0-9]+}' , '/users/abc' ) ;
// null (la contrainte a échoué)

matchSlimPattern( '/users[/{id:[0-9]+}]' , '/users' ) ;
// [] (matché, pas d'args capturés)

matchSlimPattern( '/users[/{id:[0-9]+}]' , '/users/42' ) ;
// [ 'id' => '42' ]
```

Cas d'usage :
- *seeding* de permissions depuis une table de routes sans requête vivante (Casbin policy generation, `AuthRoutesDumpCommand`-style tooling, OpenAPI)
- tests unitaires de motifs Slim en isolation
- routing léger en entry points non-Slim

## Conversion vers Casbin

### `slimToCasbinPattern( string $pattern ) : string`

Version **pure-string** de `casbinRoutePattern`. Remplace chaque `{name}` / `{name:regex}` par `:name`. Les `[...]` optionnels sont **préservés** — composez avec `expandOptionalSegments` pour obtenir une entrée Casbin par variante concrète.

```php
use function oihana\http\helpers\slimToCasbinPattern ;
use function oihana\http\helpers\expandOptionalSegments ;

slimToCasbinPattern( '/users/{id:[0-9]+}' ) ;
// '/users/:id'

slimToCasbinPattern( '/users[/{id}]' ) ;
// '/users[/:id]'  (crochets préservés)

// Workflow seeding : expansion puis conversion
foreach ( expandOptionalSegments( '/users[/{id:[0-9]+}]' ) as $variant )
{
    $canonical = slimToCasbinPattern( $variant ) ;
    // '/users' puis '/users/:id'
}
```

### `casbinRoutePattern( ServerRequestInterface $request ) : string`

Variante **Request-aware** historique : inspecte la route Slim vivante via `RouteContext::fromRequest()` et substitue chaque segment du path qui matche une valeur d'argument capturée par `:name`. Pratique dans un middleware où vous avez déjà la requête en main.

```php
use function oihana\http\helpers\casbinRoutePattern ;

// GET /users/42 (Slim pattern : /users/{id:[0-9]+})
casbinRoutePattern( $request ) ; // '/users/:id'

// DELETE /policies/75459030 (Slim pattern : /policies[/{id:[0-9]+}])
casbinRoutePattern( $request ) ; // '/policies/:id'
```

Fallback sur le path brut si aucune route Slim n'est attachée.

## Résumé : quelle fonction utiliser ?

| Besoin | Helper |
|---|---|
| À partir d'une **requête vivante** Slim | `casbinRoutePattern` |
| À partir d'une **chaîne de pattern** | `slimToCasbinPattern` |
| **Lister** toutes les variantes d'un pattern avec optionnels | `expandOptionalSegments` |
| **Compiler** en regex pour matching custom | `slimToRegex` |
| **Matcher** un path et extraire les args | `matchSlimPattern` |

## Voir aussi

- [Démarrage](getting-started.md) — exemple complet de seeding Casbin.
