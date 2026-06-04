# Détection d'IP

Le dossier `helpers/ips/` contient 13 helpers qui résolvent le problème récurrent de l'identification de la **vraie** IP du client derrière une chaîne de reverse proxies, plus l'anonymisation conforme RGPD. Le helper principal est `getClientIp()` ; les autres sont des briques de base réutilisables.

| Helper | À quoi ça sert |
|---|---|
| `getClientIp()` | Principal — résout la vraie IP client derrière des proxies de confiance (parcours droite-à-gauche de la chaîne forwarded). |
| `anonymizeIp()` | RGPD — route IPv4 vers `/24`, IPv6 vers `/48` ; les non-IP passent inchangées. |
| `truncateIpToSlash24()` | Anonymise une IPv4 en `/24` (no-op sur IPv6 / invalide). |
| `truncateIpToSlash48()` | Anonymise une IPv6 en `/48` (no-op sur IPv4 / invalide). |
| *9 briques de base* | Pièces bas-niveau (`isPublicIp`, `ipMatchesCidr`, `canonicalIp`, `walkForwardedChain`, …) — voir [Briques de base](#briques-de-base). |

## Modèle de confiance

`X-Forwarded-For` et ses cousins sont **contrôlables par le client** si vous ne les filtrez pas. Le modèle de la lib est :

1. **Vous déclarez vos proxies de confiance** sous forme d'une liste de CIDR (ou d'IPs uniques).
2. La lib parcourt la chaîne *forwarded* **de droite à gauche**, en sautant chaque saut qui provient d'une IP de confiance.
3. La première IP non-fiable rencontrée est considérée comme le vrai client.
4. Si toute la chaîne est de confiance (ou vide), la lib retombe sur `REMOTE_ADDR`.

Ça évite le spoofing classique où un client malveillant envoie `X-Forwarded-For: 1.2.3.4` pour usurper cette IP.

## `getClientIp( ?ServerRequestInterface $request = null , array $trustedProxies = [] , bool $allowPrivate = true , bool $useForwarded = false ) : ?string`

Point d'entrée. Parcourt la chaîne *forwarded*, retombe sur `Forwarded` (RFC 7239, opt-in), `X-Real-IP`, puis `REMOTE_ADDR`. Retourne une chaîne IPv4 ou IPv6 canonisée, ou `null` si aucune IP utilisable n'a été trouvée.

```php
use function oihana\http\helpers\ips\getClientIp ;

$ip = getClientIp( $request ,
[
    '10.0.0.0/8'       , // RFC 1918 privée
    '172.16.0.0/12'    ,
    '192.168.0.0/16'   ,
    '103.21.244.0/22'  , // exemple : une plage Cloudflare
]) ;

// Filtre strict des IPs publiques
$publicIp = getClientIp( $request , $trustedProxies , allowPrivate: false ) ;

// Active le parsing RFC 7239 Forwarded:
$ip = getClientIp( $request , $trustedProxies , useForwarded: true ) ;
```

## Anonymisation RGPD

Trois helpers pour anonymiser avant logging :

### `truncateIpToSlash24( ?string $ip ) : ?string`

Tronque une IPv4 à `/24` (dernier octet zéroé). **IPv4 uniquement** — IPv6 et entrées invalides passent telles quelles (no-op).

```php
use function oihana\http\helpers\ips\truncateIpToSlash24 ;

truncateIpToSlash24( '203.0.113.42' ) ; // '203.0.113.0'
truncateIpToSlash24( '2001:db8::1' ) ;  // '2001:db8::1' (no-op IPv6)
truncateIpToSlash24( null ) ;           // null
```

### `truncateIpToSlash48( ?string $ip ) : ?string`

Counterpart IPv6 : tronque à `/48` (les 80 bits de poids faible zéroés). Recommandé par BfDI/BSI pour les logs IPv6 (équivalent fonctionnel du `/24` IPv4). **IPv6 uniquement** — IPv4 et invalides passent.

```php
use function oihana\http\helpers\ips\truncateIpToSlash48 ;

truncateIpToSlash48( '2001:db8:cafe:1234::1' ) ; // '2001:db8:cafe::'
truncateIpToSlash48( '203.0.113.42' ) ;          // '203.0.113.42' (no-op IPv4)
```

### `anonymizeIp( ?string $ip ) : ?string`

Point d'entrée unifié : route les IPv4 vers `/24` et les IPv6 vers `/48`. Tout ce qui n'est pas une IP valide passe.

```php
use function oihana\http\helpers\ips\anonymizeIp ;

$auditedIp = anonymizeIp( getClientIp( $request , $trustedProxies ) ) ;

// IPv4 :  203.0.113.7                              -> 203.0.113.0
// IPv6 :  2001:db8:cafe:1234:5678:9abc:def0:1111   -> 2001:db8:cafe::
// null  : null                                     -> null
```

## Briques de base

| Helper | Ce qu'il fait |
|---|---|
| `walkForwardedChain( array $chain , array $trustedProxies ) : ?string` | Parcours droite-à-gauche d'une chaîne déjà parsée. Retourne l'IP non-fiable la plus à droite. |
| `parseForwardedHeader( string $value ) : array` | Parse un en-tête [RFC 7239](https://www.rfc-editor.org/rfc/rfc7239) `Forwarded:` en liste de valeurs `for=`. |
| `extractIpCandidatesFromRequest( ServerRequestInterface $request , bool $useForwarded ) : array` | Lit `X-Forwarded-For`, `Forwarded`, `X-Real-IP`, `CF-Connecting-IP`, etc. depuis la requête PSR-7. |
| `extractIpCandidatesFromGlobals( bool $useForwarded ) : array` | Idem mais lit directement dans `$_SERVER`. |
| `canonicalIp( string $ip ) : ?string` | Normalise une chaîne IP : IPv6 en minuscules, supprime zone IDs, collapse IPv6-mapped-IPv4. |
| `ipMatchesCidr( string $ip , string $cidr ) : bool` | Match contre un CIDR. IPv4 et IPv6. IP seule sans `/n` matche exactement. |
| `ipInList( string $ip , array $cidrs ) : bool` | `ipMatchesCidr` vectorisé. |
| `isPublicIp( string $ip ) : bool` | Vrai si l'IP est dans une plage routable globalement. |
| `acceptIp( ?string $ip , bool $allowPrivate ) : ?string` | Valide une IP et retourne sa forme canonique, ou `null`. Filtre les private/reserved si `$allowPrivate = false`. |

## Pattern de mocking dans les tests

```php
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\ips\getClientIp ;

$request = ( new ServerRequestFactory() )
    ->createServerRequest( 'GET' , '/' , [ 'REMOTE_ADDR' => '10.0.0.1' ] )
    ->withHeader( 'X-Forwarded-For' , '203.0.113.7, 10.0.0.1' ) ;

$ip = getClientIp( $request , [ '10.0.0.0/8' ] ) ;
// '203.0.113.7' — vrai client au-delà du saut proxy
```

Le même pattern est utilisé dans les tests IP de la lib sous `tests/oihana/http/helpers/ips/`.

## Voir aussi

- [Request helpers](request.md) — `isHttpsRequest()` partage le même modèle de trusted-proxy.
