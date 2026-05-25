# Démarrage

## Installation

```bash
composer require oihana/php-http
```

Nécessite PHP 8.4+. Le package dépend de `psr/http-message` à l'exécution uniquement — choisissez n'importe quelle implémentation PSR-7 (Slim PSR-7, Nyholm/psr7, Laminas Diactoros, Guzzle PSR-7…).

## Visite éclair

Voici un parcours complet de requête utilisant les trois familles principales de helpers.

```php
use Psr\Http\Message\ServerRequestInterface ;

use function oihana\http\helpers\ips\getClientIp ;
use function oihana\http\helpers\ips\truncateIpToSlash24 ;
use function oihana\http\helpers\cookies\buildSetCookieHeader ;
use function oihana\http\helpers\casbinRoutePattern ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SameSite        ;

function handleLogin( ServerRequestInterface $request , string $token ) : array
{
    // 1. IP réelle du client, en n'honorant X-Forwarded-For que pour vos proxies de confiance
    $clientIp = getClientIp( $request , [ '10.0.0.0/8' , '172.16.0.0/12' ] ) ;

    // 2. IP anonymisée RGPD pour les journaux d'audit (dernier octet à 0 en IPv4, /48 en IPv6)
    $auditIp = truncateIpToSlash24( $clientIp ) ;

    // 3. Set-Cookie pour la session
    $cookie = buildSetCookieHeader( 'session' , $token ,
    [
        CookieAttribute::HTTP_ONLY => true ,
        CookieAttribute::SECURE    => true ,
        CookieAttribute::SAME_SITE => SameSite::STRICT ,
        CookieAttribute::PATH      => '/' ,
        CookieAttribute::MAX_AGE   => 3600 ,
    ]) ;

    // 4. Traduire une route Slim en motif de policy Casbin
    $casbinPattern = casbinRoutePattern( '/users/{id}/sessions/{sid}' ) ;
    // -> '/users/*/sessions/*'

    return [ 'ip' => $clientIp , 'auditIp' => $auditIp , 'cookie' => $cookie , 'casbinPattern' => $casbinPattern ] ;
}
```

## Mocking des requêtes PSR-7 dans vos tests

Les helpers sous `helpers/ips/` acceptent une `Psr\Http\Message\ServerRequestInterface`. Pour tester avec des en-têtes synthétiques, utilisez n'importe quelle factory PSR-7. La suite de tests de la lib utilise `Slim\Psr7\Factory\ServerRequestFactory` (déclarée en `require-dev`).

```php
use Slim\Psr7\Factory\ServerRequestFactory ;

$factory = new ServerRequestFactory() ;
$request = $factory->createServerRequest( 'GET' , '/api/me' )
                   ->withHeader( 'X-Forwarded-For' , '203.0.113.7, 198.51.100.10' )
                   ->withHeader( 'X-Real-IP' , '203.0.113.7' ) ;

$ip = getClientIp( $request , [ '10.0.0.0/8' ] ) ;
```

## Étapes suivantes

- [Détection d'IP (ips/)](ips.md) — catalogue complet des 11 helpers IP.
- [Cookies](cookies.md) — construire et expirer des cookies avec attributs typés.
- [Motifs de route](route-patterns.md) — motifs Slim, segments optionnels, traduction Casbin.
