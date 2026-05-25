# Détection d'IP

Le dossier `helpers/ips/` contient 11 helpers qui résolvent le problème récurrent de l'identification de la **vraie** IP du client derrière une chaîne de reverse proxies. Le helper principal est `getClientIp()` ; les dix autres sont des briques de base réutilisables.

## Modèle de confiance

`X-Forwarded-For` et ses cousins sont **contrôlables par le client** si vous ne les filtrez pas. Le modèle de la lib est :

1. **Vous déclarez vos proxies de confiance** sous forme d'une liste de CIDR (ou d'IPs uniques).
2. La lib parcourt la chaîne *forwarded* **de droite à gauche**, en sautant chaque saut qui provient d'une IP de confiance.
3. La première IP non-fiable rencontrée est considérée comme le vrai client.
4. Si toute la chaîne est de confiance (ou vide), la lib retombe sur `REMOTE_ADDR`.

Ça évite le spoofing classique où un client malveillant envoie `X-Forwarded-For: 1.2.3.4` pour usurper cette IP.

## `getClientIp( Request $request , array $trustedProxies = [] ) : ?string`

Point d'entrée. Parcourt la chaîne *forwarded*, retombe sur `Forwarded` (RFC 7239), `X-Real-IP`, puis `REMOTE_ADDR`. Retourne une chaîne IPv4 ou IPv6 canonisée, ou `null` si aucune IP utilisable n'a été trouvée.

```php
use function oihana\http\helpers\ips\getClientIp ;

$ip = getClientIp( $request ,
[
    '10.0.0.0/8'       , // RFC 1918 privée
    '172.16.0.0/12'    ,
    '192.168.0.0/16'   ,
    '103.21.244.0/22'  , // exemple : une plage Cloudflare
]) ;
```

## Briques de base

| Helper | Ce qu'il fait |
|---|---|
| `walkForwardedChain( array $chain , array $trustedProxies ) : ?string` | Parcours droite-à-gauche d'une chaîne déjà parsée. Retourne l'IP non-fiable la plus à droite. |
| `parseForwardedHeader( string $value ) : array` | Parse un en-tête [RFC 7239](https://www.rfc-editor.org/rfc/rfc7239) `Forwarded:` (par ex. `for="[2001:db8::1]:80";proto=https`) en liste de valeurs `for=`. |
| `extractIpCandidatesFromRequest( Request $request ) : array` | Lit `X-Forwarded-For`, `Forwarded`, `X-Real-IP` depuis la requête et retourne les candidats dans l'ordre. |
| `extractIpCandidatesFromGlobals( array $server = null ) : array` | Idem mais lit directement dans `$_SERVER` — utile depuis un *bootstrap* de middleware qui n'a pas encore de requête PSR-7. |
| `canonicalIp( string $ip ) : ?string` | Normalise une chaîne IP : passe l'IPv6 en minuscules, supprime les *zone IDs* (`fe80::1%eth0` → `fe80::1`), collapse les IPv6-mapped-IPv4 (`::ffff:1.2.3.4` → `1.2.3.4`). Retourne `null` si la chaîne n'est pas une IP valide. |
| `ipMatchesCidr( string $ip , string $cidr ) : bool` | Match booléen contre un CIDR. Supporte IPv4 (`10.0.0.0/8`) et IPv6 (`2001:db8::/32`). Une IP seule sans `/n` matche exactement. |
| `ipInList( string $ip , array $cidrs ) : bool` | `ipMatchesCidr` vectorisé contre une liste. |
| `isPublicIp( string $ip ) : bool` | Vrai si l'IP est dans une plage routable globalement (pas RFC 1918 privée, pas loopback, pas link-local, pas multicast). |
| `acceptIp( string $ip , array $allowList , array $denyList = [] ) : bool` | Filtre composite : une IP est acceptée si elle matche la *allow list* ET n'est pas dans la *deny list*. *Allow list* vide = tout accepter. |
| `truncateIpToSlash24( ?string $ip ) : ?string` | Anonymisation RGPD : met à zéro le dernier octet d'une IPv4 et les 80 derniers bits d'une IPv6 (en pratique une troncature `/48`). Retourne `null` sur entrée `null`. |

## Anonymisation RGPD

Pour les journaux d'audit, alertes de sécurité et analytics, **tronquez avant la persistence** :

```php
use function oihana\http\helpers\ips\truncateIpToSlash24 ;

$auditedIp = truncateIpToSlash24( getClientIp( $request , $trustedProxies ) ) ;

// IPv4 :  203.0.113.7  -> 203.0.113.0
// IPv6 :  2001:db8:abc:def:1234:5678:90ab:cdef  -> 2001:db8:abc::
// null  :  null         -> null  (pas d'exception)
```

Ça suffit pour grouper les utilisateurs par ville/FAI sans stocker de donnée personnelle.

## Pattern de mocking dans les tests

Les 11 helpers sont des fonctions pures de leurs entrées. Tester avec `Slim\Psr7\Factory\ServerRequestFactory` est direct :

```php
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\ips\getClientIp ;

$factory = new ServerRequestFactory() ;
$request = $factory->createServerRequest( 'GET' , '/' )
                   ->withHeader( 'X-Forwarded-For' , '203.0.113.7, 10.0.0.1' ) ;

$server = $_SERVER ;
$_SERVER[ 'REMOTE_ADDR' ] = '10.0.0.1' ;        // reverse proxy de confiance

$ip = getClientIp( $request , [ '10.0.0.0/8' ] ) ;
$this->assertSame( '203.0.113.7' , $ip ) ;       // vrai client au-delà du saut proxy

$_SERVER = $server ;
```

Le même pattern est utilisé dans les 109 tests IP de la lib sous `tests/oihana/http/helpers/ips/`.
