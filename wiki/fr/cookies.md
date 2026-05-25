# Cookies

Le dossier `helpers/cookies/` contient deux helpers pour construire des en-têtes `Set-Cookie` conformes aux standards sans jongler avec la concaténation de chaînes. Tous les attributs passent par trois enums typés — pas de chaînes magiques, pas d'attributs oubliés.

## `buildSetCookieHeader( string $name , string $value , array $attributes = [] ) : string`

Construit une valeur d'en-tête `Set-Cookie` à partir d'un `name`, d'un `value`, et d'une map d'attributs.

```php
use function oihana\http\helpers\cookies\buildSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SameSite        ;

$header = buildSetCookieHeader( 'session' , 'abc123' ,
[
    CookieAttribute::HTTP_ONLY => true              ,
    CookieAttribute::SECURE    => true              ,
    CookieAttribute::SAME_SITE => SameSite::STRICT  ,
    CookieAttribute::PATH      => '/'               ,
    CookieAttribute::DOMAIN    => 'example.com'     ,
    CookieAttribute::MAX_AGE   => 3600              ,
    CookieAttribute::EXPIRES   => 'Wed, 09 Jun 2026 10:18:14 GMT' ,
]) ;

// session=abc123; Domain=example.com; Path=/; Expires=Wed, 09 Jun 2026 10:18:14 GMT;
//   Max-Age=3600; HttpOnly; Secure; SameSite=Strict
```

La valeur n'est **pas URL-encodée** automatiquement — encodez-la vous-même si elle contient des caractères réservés. Les attributs sont émis dans l'ordre canonique quel que soit l'ordre du tableau d'entrée.

## `expireSetCookieHeader( string $name , array $attributes = [] ) : string`

Émet un `Set-Cookie` de suppression pour le nom donné : `Max-Age=0` + `Expires=` dans le passé. Réutilisez les mêmes `Path` / `Domain` que ceux utilisés pour poser le cookie, sinon le navigateur ne fera pas correspondre la suppression.

```php
use function oihana\http\helpers\cookies\expireSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;

$header = expireSetCookieHeader( 'session' ,
[
    CookieAttribute::PATH   => '/'           ,
    CookieAttribute::DOMAIN => 'example.com' ,
]) ;

// session=; Domain=example.com; Path=/; Expires=Thu, 01 Jan 1970 00:00:00 GMT; Max-Age=0
```

## Enum `CookieAttribute`

Constantes typées pour tous les attributs de cookie supportés par la spec.

| Constante | Attribut d'en-tête | Type valeur |
|---|---|---|
| `CookieAttribute::DOMAIN` | `Domain` | `string` |
| `CookieAttribute::PATH` | `Path` | `string` |
| `CookieAttribute::EXPIRES` | `Expires` | chaîne date RFC 1123 |
| `CookieAttribute::MAX_AGE` | `Max-Age` | `int` (secondes) |
| `CookieAttribute::HTTP_ONLY` | `HttpOnly` | `bool` (présence seule, pas de valeur) |
| `CookieAttribute::SECURE` | `Secure` | `bool` |
| `CookieAttribute::SAME_SITE` | `SameSite` | valeur `SameSite::*` |
| `CookieAttribute::PRIORITY` | `Priority` | `string` (`Low` / `Medium` / `High`) |
| `CookieAttribute::PARTITIONED` | `Partitioned` | `bool` |

## Enum `SameSite`

| Constante | Valeur d'en-tête | Signification |
|---|---|---|
| `SameSite::STRICT` | `Strict` | Cookie envoyé **uniquement** sur les requêtes same-site — le plus dur, casse les liens cross-site vers des pages authentifiées. |
| `SameSite::LAX` | `Lax` | Cookie envoyé sur les requêtes same-site + navigation top-level en GET. Default navigateur depuis 2020. |
| `SameSite::NONE` | `None` | Cookie envoyé sur toutes les requêtes cross-site. **Requiert `Secure=true`** — les navigateurs rejettent `SameSite=None` sur HTTP en clair. |

## Valeurs par défaut défensives

Pour des cookies de session derrière HTTPS, la baseline recommandée est :

```php
CookieAttribute::HTTP_ONLY => true              ,
CookieAttribute::SECURE    => true              ,
CookieAttribute::SAME_SITE => SameSite::STRICT  ,
CookieAttribute::PATH      => '/'               ,
```

Passez `SameSite` à `LAX` uniquement si vous avez besoin que les cookies survivent à des navigations top-level GET depuis des sites externes (cas typique des callbacks SSO).
