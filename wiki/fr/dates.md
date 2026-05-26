# Dates HTTP

Le dossier `helpers/dates/` fournit deux helpers pour les en-têtes HTTP qui transportent des dates : `Date`, `Last-Modified`, `Expires`, `If-Modified-Since`, `If-Unmodified-Since`, `Retry-After`.

RFC 7231 §7.1.1.1 légalise **trois** formats pour ces dates :

| Format | Exemple | Statut |
|---|---|---|
| IMF-fixdate (RFC 7231) | `Sun, 06 Nov 1994 08:49:37 GMT` | moderne, recommandé |
| RFC 850 | `Sunday, 06-Nov-94 08:49:37 GMT` | obsolète mais encore vu en production |
| asctime | `Sun Nov  6 08:49:37 1994` | obsolète |

Les trois utilisent **GMT** par spec — pas `UTC`, pas d'offset numérique. Notre parser accepte les trois en lecture, notre émetteur n'écrit que l'IMF-fixdate.

## `parseHttpDate( ?string $value ) : ?DateTimeImmutable`

Parse une valeur d'en-tête HTTP-date en `DateTimeImmutable` UTC. Retourne `null` sur entrée `null`, vide, ou non parseable.

```php
use function oihana\http\helpers\dates\parseHttpDate ;

$dt = parseHttpDate( 'Thu, 31 Dec 2026 23:59:59 GMT' ) ;
$dt->format( DATE_ATOM ) ;          // '2026-12-31T23:59:59+00:00'
$dt->getTimezone()->getName() ;     // 'UTC'

parseHttpDate( null ) ;             // null
parseHttpDate( 'tomorrow' ) ;       // null (pas un HTTP-date)
parseHttpDate( '2026-12-31' ) ;     // null (ISO 8601, pas RFC 7231)
```

Le parser est **strict** sur la forme : il rejette `UTC` ou `+0000` au lieu de `GMT`, même si ces variantes seraient numériquement équivalentes.

## `formatHttpDate( DateTimeInterface $dt ) : string`

Formate un `DateTimeInterface` en chaîne IMF-fixdate. L'entrée est convertie en UTC avant formatage (les HTTP-dates doivent toujours se terminer par `GMT`), et le helper ne mute jamais son entrée.

```php
use function oihana\http\helpers\dates\formatHttpDate ;

$utc = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;
formatHttpDate( $utc ) ;
// 'Thu, 31 Dec 2026 23:59:59 GMT'

// Non-UTC : converti avant formatage.
$cest = new DateTimeImmutable( '2026-07-01 00:00:00' , new DateTimeZone( 'Europe/Paris' ) ) ;
formatHttpDate( $cest ) ;
// 'Tue, 30 Jun 2026 22:00:00 GMT'
```

## Roundtrip

Les deux fonctions sont conçues pour faire un roundtrip propre :

```php
$original = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;
$header   = formatHttpDate( $original ) ;
$reparsed = parseHttpDate( $header ) ;

$original->getTimestamp() === $reparsed->getTimestamp() ;  // true
```

## Pourquoi pas `gmdate('D, d M Y H:i:s \G\M\T', $ts)` ?

Trois raisons :

1. `gmdate` produit la bonne chaîne **uniquement** sur un timestamp ; il ne sait pas convertir un `DateTimeInterface` portant un fuseau ;
2. Le côté lecture (`parseHttpDate`) doit accepter les **trois** formats légaux — il faut une boucle de tentatives et un `createFromFormat` strict par variante ;
3. Centraliser l'usage de `org\common\DateFormat::RFC7231` du paquet `oihana/php-standards` évite que chaque consommateur réécrive la chaîne de format à la main.

## Voir aussi

- [Cookies](cookies.md) — l'attribut `Expires` du `Set-Cookie` utilise le même format IMF-fixdate (émis automatiquement par `buildSetCookieHeader` quand l'option `CookieOption::EXPIRES` est posée).
