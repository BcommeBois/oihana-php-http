# Motifs de route

Deux petits mais récurrents problèmes quand on travaille avec les motifs de route style Slim :

1. Un motif portant des **segments optionnels entre crochets** (`/users[/{id:[0-9]+}]`) enregistre une seule route Slim qui sert plusieurs formes d'URL. Les outils qui ont besoin d'une correspondance 1:1 entre une ligne de route et autre chose (permission seedée, policy Casbin, chemin OpenAPI auto-généré) doivent d'abord expanser chaque optionnel en ses variantes concrètes.
2. **Traduire un motif Slim en motif de policy Casbin** demande de collapser chaque `{placeholder}` en `*` (wildcard Casbin) en préservant la structure du chemin.

`oihana/php-http` fournit un helper pour chacun.

## `expandOptionalSegments( string $pattern ) : array`

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

### Les crochets dans `{...}` ne sont PAS des groupes optionnels

`[0-9]` à l'intérieur de `{id:[0-9]+}` fait partie d'une classe de caractères regex — Slim le parse comme la contrainte sur le placeholder `id`. L'implémentation suit la profondeur des accolades et **ne traite pas** les crochets dans `{...}` comme des segments optionnels.

```php
expandOptionalSegments( '/items/{key:[A-Z]{2,4}}' ) ;
// [ '/items/{key:[A-Z]{2,4}}' ]   // pas d'expansion, les crochets sont dans {...}
```

### Usage pratique

Le *seeding* des permissions dans `oihana/php-auth` appelle ce helper avant de mapper chaque motif concret à une policy Casbin. Idem pour la commande de test d'intégration `AuthTestServiceProbeCommand` quand elle parcourt le registre des routes Slim pour vérifier que chaque paire verbe-chemin annoncée a une permission.

## `casbinRoutePattern( string $slimPattern ) : string`

Traduit un motif de route Slim (après expansion des optionnels) en motif de policy Casbin en collapsant `{placeholder}` en `*`.

```php
use function oihana\http\helpers\casbinRoutePattern ;

casbinRoutePattern( '/users/{id}' ) ;
// '/users/*'

casbinRoutePattern( '/users/{id}/sessions/{sid}' ) ;
// '/users/*/sessions/*'

casbinRoutePattern( '/products' ) ;
// '/products'  (pas de placeholder, retourné tel quel)

casbinRoutePattern( '/items/{key:[A-Z]{2,4}}' ) ;
// '/items/*'  (contrainte supprimée avec le placeholder)
```

### Usage avec les matchers Casbin

Dans un fichier de policy Casbin, le motif résultant est matché avec `keyMatch2` ou `globMatch` :

```ini
[matchers]
m = keyMatch2( r.obj , p.obj ) && r.act == p.act
```

```csv
p, role:editor, /users/*,             GET
p, role:editor, /users/*/sessions/*,  GET
```

## Combinaison des deux

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

Notez le doublon légitime `/users/*` — quand deux variantes concrètes collapsent vers le même motif Casbin, dédupliquez avant le seeding.
