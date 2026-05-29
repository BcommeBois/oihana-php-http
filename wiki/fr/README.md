# oihana/php-http — Guide utilisateur

![Langue](https://img.shields.io/badge/langue-Français-blue)

`oihana/php-http` est une bibliothèque PHP composable pour le code orienté HTTP : détection de l'IP réelle du client derrière les reverse proxies, anonymisation conforme RGPD, builders et parsers typés de cookies, helpers PSR-7 d'authentification et d'inspection de requête, négociation de contenu, dates HTTP, URL/query string, signatures HMAC pour URLs signées et webhooks, parser User-Agent. Compatible PSR-7, zéro chaîne magique, zéro dépendance externe (hors écosystème oihana).

## À qui s'adresse cette documentation

Aux développeurs PHP qui construisent une API derrière un ou plusieurs reverse proxies (Cloudflare, nginx, AWS ALB, …) et qui doivent :

- identifier l'IP réelle du client, avec un contrôle complet sur la chaîne de proxies de confiance, et tronquer pour le RGPD (IPv4 `/24`, IPv6 `/48`) ;
- émettre des cookies sécurisés sans oublier `SameSite`, `Secure`, `HttpOnly`, `Partitioned` ;
- valider les en-têtes `Authorization` (Bearer, Basic), inspecter rapidement une requête (`wantsJson`, `isAjax`, `isHttpsRequest`) ;
- négocier le contenu (`Accept*`, `parseContentType`) ;
- parser et formater les dates HTTP (RFC 7231 IMF-fixdate, RFC 850, asctime) ;
- manipuler les URLs et query strings sans perdre les doublons ;
- signer des URLs avec TTL et vérifier des webhooks HMAC en temps constant ;
- parser un User-Agent pour le routing analytics / bot detection.

## Démarrage rapide

```php
use function oihana\http\helpers\auth\getBearerToken                 ;
use function oihana\http\helpers\cookies\buildSetCookieHeader        ;
use function oihana\http\helpers\ips\getClientIp                     ;
use function oihana\http\helpers\request\wantsJson                   ;
use function oihana\http\helpers\signatures\verifyHmacSignature      ;

use oihana\http\enums\CookieOption ;
use oihana\http\enums\SameSite     ;

// 1. IP du client derrière votre CIDR de reverse proxy
$clientIp = getClientIp( $request , [ '10.0.0.0/8' , '192.168.0.0/16' ] ) ;

// 2. Cookie de session sécurisé
$header = buildSetCookieHeader( 'session' , $token , 3600 ,
[
    CookieOption::SECURE      => true               ,
    CookieOption::SAME_SITE   => SameSite::STRICT   ,
    CookieOption::PATH        => '/'                ,
]) ;

// 3. Token Bearer + négociation JSON
$token = getBearerToken( $request ) ;
$wantsJson = wantsJson( $request ) ;

// 4. Webhook Stripe / GitHub / Slack
$payload = (string) $request->getBody() ;
$sig     = $request->getHeaderLine( 'X-Webhook-Signature' ) ;
if ( !verifyHmacSignature( $payload , $sig , $webhookSecret ) )
{
    return new Response( 401 ) ;
}
```

## Sommaire

### Briques HTTP

- **[Démarrage](getting-started.md)** — installation, mocking PSR-7, premiers exemples.
- **[Détection d'IP](ips.md)** — `getClientIp`, anonymisation RGPD (`truncateIpToSlash24/48`, `anonymizeIp`), parsing `Forwarded` (RFC 7239), matching CIDR.
- **[Cookies](cookies.md)** — `buildSetCookieHeader`, `expireSetCookieHeader`, `parseCookieHeader`, `parseSetCookieHeader`, validation `validateCookie{Name,Value}`, enums `CookieAttribute` / `CookieOption` / `CookiePriority` / `SameSite` / `SetCookieField`.
- **[Authorization](authorization.md)** — `parseAuthorizationHeader`, `getBearerToken`, `getBasicAuth`, enums `AuthorizationField` / `BasicAuthField`.
- **[Helpers de requête PSR-7](request.md)** — `wantsJson`, `isAjax`, `isHttpsRequest`.
- **[Négociation de contenu](negotiation.md)** — `parseAcceptHeader` (universel pour `Accept`, `Accept-Language`, `Accept-Encoding`), `negotiate`, `parseContentType`, enums `AcceptField` / `ContentTypeField`.
- **[Dates HTTP](dates.md)** — `parseHttpDate` (3 formats RFC 7231), `formatHttpDate` (IMF-fixdate).
- **[URL / Query string](urls.md)** — `parseQueryString`, `buildQueryString`, `withQueryParams`, `removeQueryParam`, `normalizeUrl`, `isAbsoluteUrl`.

### Au-dessus

- **[User-Agent](user-agent.md)** — `parseUserAgent`, detect helpers, `isBotUserAgent`, `isMobileUserAgent`, enums `BrowserName` / `OsName` + DTO `UserAgentInfo` (`oihana/php-schema`).
- **[Signatures HMAC](signatures.md)** — `signUrl`, `verifySignedUrl`, `verifyHmacSignature` (Stripe / GitHub / Slack / Mailchimp), enums `SignatureFormat` / `SignedUrlField`.

### Transverse

- **[Sécurité](security.md)** — modèle trusted-proxy, anonymisation RGPD, validation cookies, constant-time HMAC, URL canonicalization, base64url strict, anti-CRLF dans le parsing, baselines recommandées, ce qui n'est PAS couvert.

## Code source

Le code de la bibliothèque vit sous [`src/oihana/http/`](../../src/oihana/http/).

## Voir aussi

- [Packagist `oihana/php-http`](https://packagist.org/packages/oihana/php-http) — page du package.
- [`oihana/php-auth`](https://github.com/BcommeBois/oihana-php-auth) — Casbin RBAC + JWT/OIDC ; consomme les helpers IP et cookies.
- [`oihana/php-enums`](https://github.com/BcommeBois/oihana-php-enums) — constantes HTTP typées (`HttpHeader`, `HttpStatusCode`, `AuthScheme`, …).
- [`oihana/php-schema`](https://github.com/BcommeBois/oihana-php-schema) — DTO partagés (`UserAgentInfo`, `Session`, …).
- [`oihana/php-standards`](https://github.com/BcommeBois/oihana-php-standards) — formats date standards (`DateFormat::RFC7231`).
- [`oihana/php-core`](https://github.com/BcommeBois/oihana-php-core) — encoding (`base64UrlEncode` / `base64UrlDecode`).
- [`oihana/php-files`](https://github.com/BcommeBois/oihana-php-files) — `joinPaths()` pour la concaténation de chemins URL.
