# oihana/php-http — Guide utilisateur

![Langue](https://img.shields.io/badge/langue-Français-blue)

`oihana/php-http` est une petite bibliothèque PHP pour le code orienté HTTP : détection de l'IP réelle du client derrière les reverse proxies, builders typés de `Set-Cookie`, utilitaires de motifs de route et parsing user-agent. Compatible PSR-7, zéro chaîne magique.

## À qui s'adresse cette documentation

Aux développeurs PHP qui construisent une API derrière un ou plusieurs reverse proxies (Cloudflare, nginx, AWS ALB, …) et qui doivent :

- identifier correctement l'IP réelle du client, avec un contrôle complet sur la chaîne de proxies de confiance ;
- tronquer les IPs en `/24` (IPv4) ou `/48` (IPv6) pour des journaux conformes au RGPD ;
- émettre des en-têtes `Set-Cookie` sans jongler avec la concaténation et risquer d'oublier `SameSite` ou `HttpOnly` ;
- gérer les motifs de route Slim avec des segments optionnels entre crochets — pour le *seeding* de permissions, l'autorisation route-par-route ou la correspondance 1:1 route → policy Casbin.

## Démarrage rapide

```php
use function oihana\http\helpers\ips\getClientIp ;
use function oihana\http\helpers\cookies\buildSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SameSite        ;

// 1. Détecter l'IP réelle du client en ne faisant confiance qu'à votre CIDR de reverse proxy
$clientIp = getClientIp( $request , [ '10.0.0.0/8' , '192.168.0.0/16' ] ) ;

// 2. Construire un en-tête Set-Cookie pour une session authentifiée
$header = buildSetCookieHeader( 'session' , $token ,
[
    CookieAttribute::HTTP_ONLY => true ,
    CookieAttribute::SECURE    => true ,
    CookieAttribute::SAME_SITE => SameSite::STRICT ,
    CookieAttribute::PATH      => '/' ,
    CookieAttribute::MAX_AGE   => 3600 ,
]) ;
```

## Sommaire

- [Démarrage](getting-started.md) — installation, mocking de requête PSR-7, premiers exemples.
- [Détection d'IP (ips/)](ips.md) — `getClientIp`, `walkForwardedChain`, `parseForwardedHeader`, `canonicalIp`, `ipMatchesCidr`, `ipInList`, `isPublicIp`, `acceptIp`, `truncateIpToSlash24`, `extractIpCandidatesFrom*`.
- [Cookies](cookies.md) — `buildSetCookieHeader`, `expireSetCookieHeader`, enums typés `CookieAttribute` / `CookieOption` / `SameSite`.
- [Motifs de route](route-patterns.md) — `expandOptionalSegments` (segments optionnels Slim → produit cartésien), `casbinRoutePattern` (Slim `{placeholder}` → Casbin `*`).

## Code source

Le code du framework vit sous [`src/oihana/http/`](../../src/oihana/http/).

## Voir aussi

- [Packagist `oihana/php-http`](https://packagist.org/packages/oihana/php-http) — page du package.
- [`oihana/php-auth`](https://github.com/BcommeBois/oihana-php-auth) — Casbin RBAC + JWT/OIDC, consomme les helpers de motifs de route pour le *seeding* des permissions.
- [`oihana/php-enums`](https://github.com/BcommeBois/oihana-php-enums) — constantes HTTP typées (`HttpHeader`, `HttpStatusCode`, …) utilisées dans tous les exemples.
