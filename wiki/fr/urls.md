# URL / Query string

Le dossier `helpers/url/` couvre les opérations URI les plus courantes en application code (RFC 3986). Dix helpers en string-in/string-out (ou `UriInterface`-in/out pour les manipulateurs PSR-7).

| Helper | À quoi ça sert |
|---|---|
| `parseQueryString()` | Parse une query string en map `nom → liste de valeurs` (doublons préservés). |
| `buildQueryString()` | Reconstruit une query string depuis une telle map (inverse exact). |
| `withQueryParams()` | Fusionne des params dans une URI PSR-7 (immutable ; `null` retire une clé). |
| `removeQueryParam()` | Retire une clé query (et toutes ses valeurs) d'une URI PSR-7. |
| `normalizeUrl()` | Forme canonique pour dédup / cache / comparaison (scheme+host en minuscules, port par défaut retiré, query triée). |
| `isAbsoluteUrl()` | Indique si une chaîne porte un scheme (est une URL absolue). |
| `getHost()` | Extrait l'hôte d'une URL, normalisé (minuscules, crochets IPv6 retirés), `null` si absent. |
| `isPublicUrl()` | Indique si une URL vise un hôte joignable publiquement. |
| `isLocalUrl()` | Indique si une URL vise un hôte local / privé / réservé. |
| `withUrlComponents()` | Dérive une URL à partir d'une autre en remplaçant / retirant des composants. |

## Parsing / building de query string

### `parseQueryString( string $query , bool $formEncoded = false ) : array<string, list<string>>`

Parse une query string en map `nom → liste de valeurs`. **Différences avec `parse_str()` PHP** :

- Les **doublons sont préservés** : `'a=1&a=2'` → `['a' => ['1', '2']]` (au lieu d'écraser).
- Les clés sont **opaques** : `'a[]=1&a[]=2'` → `['a[]' => ['1', '2']]` (PHP interpréterait `[]` comme syntaxe d'append).
- Chaque valeur est **toujours un array** — type prévisible, pas de surprise `string|array`.

```php
use function oihana\http\helpers\url\parseQueryString ;

parseQueryString( 'a=1&b=2' ) ;
// [ 'a' => [ '1' ] , 'b' => [ '2' ] ]

parseQueryString( 'tag=php&tag=http' ) ;
// [ 'tag' => [ 'php' , 'http' ] ]

parseQueryString( 'q=hello%20world' ) ;       // RFC 3986 (rawurldecode)
// [ 'q' => [ 'hello world' ] ]

parseQueryString( 'q=hello+world' , true ) ;  // form-encoded (+ → espace)
// [ 'q' => [ 'hello world' ] ]
```

### `buildQueryString( array $params , bool $rfc3986 = true ) : string`

Réciproque exacte. Symétrie avec `parseQueryString` — roundtrip propre.

```php
use function oihana\http\helpers\url\buildQueryString ;

buildQueryString( [ 'a' => '1' , 'b' => '2' ] ) ;
// 'a=1&b=2'

buildQueryString( [ 'tag' => [ 'php' , 'http' ] ] ) ;
// 'tag=php&tag=http'  (clés répétées, pas `tag[0]=php`)

buildQueryString( [ 'q' => 'hello world' ] ) ;
// 'q=hello%20world'  (RFC 3986, défaut)

buildQueryString( [ 'verbose' => true , 'debug' => null ] ) ;
// 'verbose=1&debug'  (true→1, false→0, null→clé nue)
```

## Manipulation PSR-7

### `withQueryParams( UriInterface $uri , array $params ) : UriInterface`

Update immutable d'un URI avec merge des params. Clés existantes remplacées, autres préservées. `null` supprime la clé.

```php
use function oihana\http\helpers\url\withQueryParams ;

// $uri = https://example.com/path?a=1
$next = withQueryParams( $uri , [ 'b' => '2' , 'a' => null ] ) ;
// $next  → https://example.com/path?b=2
// $uri   inchangé
```

### `removeQueryParam( UriInterface $uri , string $name ) : UriInterface`

Sucre syntaxique pour supprimer une clé (toutes ses valeurs si multi-valuée).

```php
use function oihana\http\helpers\url\removeQueryParam ;

removeQueryParam( $uri , 'tag' )  ;
// retire tag (et toutes ses valeurs) du query
```

## Inspection / canonicalisation

### `normalizeUrl( string $url ) : string`

Forme canonique d'une URL pour déduplication, cache, comparaison :
- scheme et host lowercasés
- port par défaut supprimé (`http:80`, `https:443`, `ws:80`, `wss:443`, `ftp:21`)
- clés de query triées alphabétiquement (les valeurs multi-valuées gardent leur ordre relatif)
- fragment préservé tel quel
- fail-open sur entrée non parseable

```php
use function oihana\http\helpers\url\normalizeUrl ;

normalizeUrl( 'HTTPS://Example.COM:443/Path?b=2&a=1' ) ;
// 'https://example.com/Path?a=1&b=2'
```

**Pas appliqué** (hors scope, demanderait une lib URI plus lourde) :
- décodage percent des caractères unreserved dans le path
- résolution dot-segment (`/a/./b/../c` → `/a/c`)
- normalisation IDN / Punycode

### `isAbsoluteUrl( string $url ) : bool`

Détection stricte du composant scheme (RFC 3986 §4.3 : `ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )` suivi de `:`).

```php
use function oihana\http\helpers\url\isAbsoluteUrl ;

isAbsoluteUrl( 'https://example.com/path' ) ; // true
isAbsoluteUrl( 'mailto:alice@example.com'  ) ; // true
isAbsoluteUrl( '//example.com/path'        ) ; // false (protocol-relative)
isAbsoluteUrl( '/api/v1'                   ) ; // false (path-absolute)
isAbsoluteUrl( 'api/v1'                    ) ; // false (relative)
```

### `getHost( string $url ) : ?string`

Extrait l'hôte d'une URL sous une forme prête pour la comparaison, l'allow-listing ou la validation IP — en minuscules et sans les crochets IPv6. Renvoie `null` quand il n'y a pas d'hôte.

```php
use function oihana\http\helpers\url\getHost ;

getHost( 'https://API.Example.com/path?x=1' ) ; // 'api.example.com'
getHost( 'http://localhost:8080' )             ; // 'localhost'
getHost( 'http://[2001:db8::1]:443/x' )        ; // '2001:db8::1'  (crochets retirés)
getHost( 'mailto:alice@example.com' )          ; // null  (pas d'autorité)
getHost( '/relative/path' )                    ; // null
```

La forme IPv6 sans crochets sert à *l'inspection*, pas à être réinjectée dans une URL (un `::1` nu n'est pas une autorité valide). La reconstruction d'URL reste le rôle de `normalizeUrl()`, qui conserve les crochets dont il a besoin.

### `isPublicUrl( string $url ) : bool`

Imaginez un installeur qui a besoin d'une URL de callback publique pour enregistrer un webhook. Si l'opérateur saisit `http://localhost:8080` ou `http://192.168.1.10`, le service distant ne pourra jamais l'atteindre — le CLI doit donc refuser tout de suite et réclamer un point d'entrée public explicite (un tunnel type ngrok / cloudflared, un reverse proxy, …). `isPublicUrl()` est exactement ce garde-fou : il regarde l'**hôte** d'une URL et indique s'il est joignable depuis l'extérieur.

- `localhost` et tout sous-domaine `*.localhost` → `false`
- les adresses IP littérales (IPv4 ou IPv6) sont confiées à [`isPublicIp()`](ips.md) : toutes les plages loopback, privées (RFC 1918 / RFC 4193) et réservées → `false`
- tout autre hôte nommé (un FQDN comme `api.example.com`) → `true`
- une entrée sans hôte (chemin relatif, chaîne vide) → `false`

```php
use function oihana\http\helpers\url\isPublicUrl ;

isPublicUrl( 'https://api.example.com'       ) ; // true
isPublicUrl( 'https://8.8.8.8'               ) ; // true
isPublicUrl( 'http://localhost:8080'         ) ; // false
isPublicUrl( 'http://app.localhost'          ) ; // false (sous-domaine)
isPublicUrl( 'http://127.0.0.1'              ) ; // false (loopback)
isPublicUrl( 'http://10.0.0.1'               ) ; // false (RFC 1918)
isPublicUrl( 'http://[::1]'                  ) ; // false (loopback)
isPublicUrl( 'http://[fd00::1]'              ) ; // false (unique local)
isPublicUrl( 'http://[2001:4860:4860::8888]' ) ; // true
isPublicUrl( '/relative/path'                ) ; // false (pas d'hôte)
```

> **Heuristique syntaxique, pas un garde-fou anti-SSRF.** Aucune résolution DNS n'est effectuée : un FQDN qui résout vers une adresse privée (`internal.corp.lan` → `10.x`) reste considéré comme public. À utiliser comme *indice de routage* (« ai-je besoin d'un point d'entrée public explicite ici ? »), pas comme une frontière de sécurité contre les requêtes falsifiées côté serveur (SSRF).

### `isLocalUrl( string $url ) : bool`

Le pendant lisible de `isPublicUrl()` — `true` quand l'URL vise un hôte local ou privé (`localhost`, `*.localhost`, IP loopback / RFC 1918 / RFC 4193 / réservée).

```php
use function oihana\http\helpers\url\isLocalUrl ;

isLocalUrl( 'http://localhost:8080' )   ; // true
isLocalUrl( 'http://127.0.0.1' )        ; // true
isLocalUrl( 'http://[::1]' )            ; // true
isLocalUrl( 'https://api.example.com' ) ; // false
isLocalUrl( '/relative/path' )          ; // false (pas d'hôte)
```

> Ce n'est pas la négation stricte de `isPublicUrl()` : une URL sans hôte n'est ni publique ni locale, donc **les deux** renvoient `false`. La présence d'un hôte est requise.

## Dériver une URL à partir d'une autre

### `withUrlComponents( string $url , array $overrides ) : string`

Parfois vous avez une URL et en voulez *presque* la même — passer `http` en `https`, changer le host, retirer les identifiants, supprimer le fragment. `withUrlComponents()` fait exactement ça : il parse l'URL, applique vos remplacements, puis la reconstruit. Les clés sont les constantes `oihana\enums\http\UrlComponent` ; une valeur `null` supprime le composant.

C'est du string-in / string-out : pas besoin d'instancier un `UriInterface` PSR-7 (ni de tirer une implémentation PSR-7) juste pour modifier un bout d'URL.

```php
use oihana\enums\http\UrlComponent ;
use function oihana\http\helpers\url\withUrlComponents ;

// Changer uniquement le scheme
withUrlComponents( 'http://example.com/path' , [ UrlComponent::SCHEME => 'https' ] ) ;
// 'https://example.com/path'

// Remplacer uniquement le mot de passe
withUrlComponents( 'https://user:old@example.com' , [ UrlComponent::PASS => 'new' ] ) ;
// 'https://user:new@example.com'

// Retirer la query et le fragment
withUrlComponents( 'https://example.com/p?x=1#frag' , [ UrlComponent::QUERY => null , UrlComponent::FRAGMENT => null ] ) ;
// 'https://example.com/p'
```

**Limites** (volontairement hors périmètre — même posture pragmatique que `normalizeUrl()`) :

- **Pas de percent-encoding.** Les valeurs sont insérées *telles quelles*. Une valeur avec des caractères réservés (un mot de passe contenant `@` ou `:`, un path avec une espace…) doit être encodée par vous, sinon l'URL produite est malformée.
- **Pas de contrôle de validité.** Le résultat n'est ni re-parsé ni validé.
- **L'hôte IPv6 garde ses crochets.** Pour fixer un hôte IPv6, passez-le entre crochets (`[::1]`). Attention à l'asymétrie avec `getHost()`, qui les *retire* — sa sortie est faite pour l'inspection, pas pour être réinjectée ici.
- **Composants liés à l'autorité.** `user` / `pass` / `port` n'apparaissent que si un hôte est présent ; retirer l'hôte les fait tomber aussi.
- **Fail-open.** Si l'URL ne peut pas être parsée, elle est renvoyée intacte et les remplacements sont ignorés.

> Vous avez déjà un `UriInterface` PSR-7 sous la main ? Préférez ses méthodes natives `withScheme()` / `withUserInfo()` / `withHost()` / … — elles gèrent l'encodage pour vous. `withUrlComponents()` est l'alternative légère, basée sur des chaînes, quand vous n'avez pas (ou ne voulez pas) d'objet URI.

## Concaténation de path

Pas de helper local — utiliser **`oihana\files\path\joinPaths()`** du paquet `oihana/php-files` (en transitivité dans nos `require`). Il gère déjà tous les cas pertinents :
- collapse des `/` redondants
- préservation du slash initial
- résolution des dot segments (RFC 3986 §6.2.2.3)
- compatibilité avec les schemes (`phar://`, `C:\`, …)

```php
use function oihana\files\path\joinPaths ;

joinPaths( '/api/v1/' , '/users/' , '/123/' ) ;
// '/api/v1/users/123'
```

Pas la peine de réimplémenter — un seul endroit dans l'écosystème, un seul comportement.

## Voir aussi

- [Signatures](signatures.md) — `signUrl()` utilise `normalizeUrl` en interne pour rendre la signature insensible à l'ordre des params query.
