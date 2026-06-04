# Cookies

Le dossier `helpers/cookies/` contient six helpers pour construire et parser des en-têtes `Set-Cookie` / `Cookie` sans jongler avec la concaténation de chaînes. Tous les attributs passent par des enums typés — zéro chaîne magique, zéro oubli.

| Helper | À quoi ça sert |
|---|---|
| `buildSetCookieHeader()` | Construit un en-tête `Set-Cookie` depuis nom / valeur / max-age / options (valide nom & valeur). |
| `expireSetCookieHeader()` | Construit un `Set-Cookie` de suppression (`value=''` + `Max-Age=0`). |
| `parseCookieHeader()` | Parse un en-tête `Cookie:` de requête en map `nom → valeur`. |
| `parseSetCookieHeader()` | Parse une ligne `Set-Cookie` en tuple `{name, value, attributes}`. |
| `validateCookieName()` | Lève une exception si le nom de cookie viole la grammaire token RFC 7230. |
| `validateCookieValue()` | Lève une exception si la valeur contient des caractères de contrôle ou `;`. |

## Builder

### `buildSetCookieHeader( string $name , ?string $value , int $maxAge , array $options = [] ) : string`

Construit un en-tête `Set-Cookie`. Le `$name` et le `$value` sont validés à l'entrée — entrées invalides → `InvalidArgumentException` (sécurité contre les attaques de CRLF / response splitting / attribute injection).

```php
use function oihana\http\helpers\cookies\buildSetCookieHeader ;

use oihana\http\enums\CookieOption ;
use oihana\http\enums\CookiePriority ;
use oihana\http\enums\SameSite ;

$header = buildSetCookieHeader
(
    'session' ,         // nom
    $token ,            // valeur (ou null)
    3600 ,              // max-age en secondes
    [
        CookieOption::SECURE      => true                   ,
        CookieOption::SAME_SITE   => SameSite::STRICT       ,
        CookieOption::PATH        => '/'                    ,
        CookieOption::DOMAIN      => 'example.com'          ,
        CookieOption::EXPIRES     => new DateTimeImmutable( '+1 hour' , new DateTimeZone( 'UTC' ) ) ,
        CookieOption::PRIORITY    => CookiePriority::HIGH   ,
        CookieOption::PARTITIONED => true                   ,
    ]
) ;
// session=…; Path=/; Max-Age=3600; SameSite=Strict; HttpOnly; Secure;
//   Domain=example.com; Expires=Thu, 31 Dec 2026 …GMT; Priority=High; Partitioned
```

Défauts appliqués (modifiables via `$options`) : `Path=/`, `HttpOnly`, `SameSite=Lax`, pas de `Domain`, pas de `Secure`, pas de `Expires`, pas de `Priority`, pas de `Partitioned`.

#### Format accepté pour `Expires`

| Type passé | Comportement |
|---|---|
| `int` | timestamp Unix, formaté en UTC en IMF-fixdate (RFC 7231) |
| `string` | passé tel quel (échappatoire pour formats exotiques) |
| `DateTimeInterface` | converti en UTC, formaté en IMF-fixdate |
| `null` ou absent | l'attribut `Expires` n'est pas émis |

#### Validation des entrées

- **Nom** : grammaire RFC 7230 `token` strict (lettres, chiffres, `! # $ % & ' * + - . ^ _ \` | ~`). Espace, séparateurs (`( ) < > @ , ; : \ " / [ ] ? = { }`) et contrôles → exception.
- **Valeur** : refus des caractères de contrôle ASCII (0x00–0x1F + 0x7F) et `;` qui briseraient le parsing. Whitespace, `"`, `,`, `\` tolérés pour interop (techniquement non strict RFC 6265). Valeur vide acceptée (utilisée par `expireSetCookieHeader`).

### `expireSetCookieHeader( string $name , array $options = [] ) : string`

Émet un `Set-Cookie` de suppression : `value=''` + `Max-Age=0`. Réutilisez les mêmes `Path` / `Domain` / `SameSite` que ceux utilisés pour poser le cookie, sinon le navigateur ne fera pas correspondre la suppression.

```php
use function oihana\http\helpers\cookies\expireSetCookieHeader ;

use oihana\http\enums\CookieOption ;

$header = expireSetCookieHeader
(
    'session' ,
    [
        CookieOption::PATH   => '/'           ,
        CookieOption::DOMAIN => 'example.com' ,
        CookieOption::SECURE => true          ,
    ]
) ;
// session=; Path=/; Max-Age=0; SameSite=Lax; HttpOnly; Secure; Domain=example.com
```

## Parsers

### `parseCookieHeader( string $header ) : array<string, string>`

Parse une valeur d'en-tête `Cookie:` (côté requête) en map `nom → valeur`. Réciproque de `buildSetCookieHeader` côté lecture.

```php
use function oihana\http\helpers\cookies\parseCookieHeader ;

parseCookieHeader( 'PHPSESSID=abc; user=jane' ) ;
// [ 'PHPSESSID' => 'abc' , 'user' => 'jane' ]
```

- Split sur le **premier** `=` (les valeurs peuvent légitimement contenir `=`, ex. padding base64).
- Valeurs retournées **verbatim**, pas d'URL-decode automatique (à vous de faire si nécessaire).
- En cas de doublon, **la dernière occurrence gagne** — comme `$_COOKIE`.

### `parseSetCookieHeader( string $header ) : array`

Parse une ligne `Set-Cookie` complète en tuple `{name, value, attributes}`. Pratique en tests pour asserter la structure d'un header généré, ou pour inspecter un cookie posé par un service amont.

```php
use function oihana\http\helpers\cookies\parseSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SetCookieField ;

$parsed = parseSetCookieHeader( 'access_token=abc; Path=/; Max-Age=3600; HttpOnly' ) ;

$parsed[ SetCookieField::NAME       ] ; // 'access_token'
$parsed[ SetCookieField::VALUE      ] ; // 'abc'
$parsed[ SetCookieField::ATTRIBUTES ] ;
// [
//   'Path'     => '/' ,
//   'Max-Age'  => '3600' ,
//   'HttpOnly' => true ,
// ]
```

Les noms d'attributs sont normalisés à la casse canonique via le lookup `CookieAttribute` (ex. `path=/` du wire devient `Path=/` dans la sortie).

## Validation autonome

### `validateCookieName( string $name ) : void`
### `validateCookieValue( string $value ) : void`

Exposés publiquement pour permettre au code applicatif de valider une entrée utilisateur **avant** de l'injecter dans un cookie. Throw `InvalidArgumentException` en cas d'invalide.

## Vocabulaires (enums)

### `CookieOption` — clés du tableau `$options`

| Constante | Clé | Valeur typique |
|---|---|---|
| `DOMAIN` | `'domain'` | `string` ou vide pour skip |
| `EXPIRES` | `'expires'` | `int\|string\|DateTimeInterface\|null` |
| `HTTP_ONLY` | `'httpOnly'` | `bool`, défaut `true` |
| `PARTITIONED` | `'partitioned'` | `bool`, défaut `false` |
| `PATH` | `'path'` | `string`, défaut `'/'` |
| `PRIORITY` | `'priority'` | constante `CookiePriority` ou `null` |
| `SAME_SITE` | `'sameSite'` | constante `SameSite`, défaut `LAX` |
| `SECURE` | `'secure'` | `bool`, défaut `false` |

### `CookieAttribute` — noms wire-format (côté lecture)

| Constante | Wire | Notes |
|---|---|---|
| `DOMAIN` | `Domain` | |
| `EXPIRES` | `Expires` | IMF-fixdate RFC 7231 |
| `HTTP_ONLY` | `HttpOnly` | flag |
| `MAX_AGE` | `Max-Age` | secondes |
| `PARTITIONED` | `Partitioned` | flag (CHIPS) |
| `PATH` | `Path` | |
| `PRIORITY` | `Priority` | `CookiePriority` |
| `SAME_SITE` | `SameSite` | `SameSite::*` |
| `SECURE` | `Secure` | flag |

### `SameSite`

| Constante | Valeur | Signification |
|---|---|---|
| `STRICT` | `Strict` | envoi uniquement same-site — le plus dur, casse les liens cross-site authentifiés |
| `LAX` | `Lax` | same-site + navigation top-level GET. Default browsers depuis 2020. |
| `NONE` | `None` | toutes requêtes cross-site. **Requiert `Secure=true`** |

### `CookiePriority`

| Constante | Valeur | Politique d'éviction |
|---|---|---|
| `LOW` | `Low` | évicté en premier sous pression de quota |
| `MEDIUM` | `Medium` | défaut quand l'attribut est absent |
| `HIGH` | `High` | évicté en dernier — réserver aux cookies de session critiques |

### `SetCookieField` — clés du tuple retourné par `parseSetCookieHeader`

| Constante | Clé | Contenu |
|---|---|---|
| `NAME` | `'name'` | nom du cookie |
| `VALUE` | `'value'` | valeur (verbatim) |
| `ATTRIBUTES` | `'attributes'` | map des attributs (`array<string, string\|true>`) |

## Baseline défensive

Pour un cookie de session derrière HTTPS, la recette recommandée :

```php
buildSetCookieHeader( 'session' , $token , 3600 ,
[
    CookieOption::SECURE    => true              ,
    CookieOption::SAME_SITE => SameSite::STRICT  ,
    CookieOption::PATH      => '/'               ,
])
```

`HttpOnly` est `true` par défaut. Passez `SameSite::LAX` uniquement si vous avez besoin que le cookie survive aux navigations top-level GET depuis des sites externes (typique des callbacks SSO).
