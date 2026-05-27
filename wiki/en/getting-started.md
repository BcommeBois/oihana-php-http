# Getting started

## Install

```bash
composer require oihana/php-http
```

Requires PHP 8.4+. The package depends on `psr/http-message` only at runtime — pick any PSR-7 implementation (Slim PSR-7, Nyholm/psr7, Laminas Diactoros, Guzzle PSR-7…).

## Two-minute tour

Below is a full request flow using the three main families of helpers.

```php
use Psr\Http\Message\ServerRequestInterface ;

use function oihana\http\helpers\ips\getClientIp ;
use function oihana\http\helpers\ips\truncateIpToSlash24 ;
use function oihana\http\helpers\cookies\buildSetCookieHeader ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SameSite        ;

function handleLogin( ServerRequestInterface $request , string $token ) : array
{
    // 1. Real client IP, honoring X-Forwarded-For only from your trusted proxies
    $clientIp = getClientIp( $request , [ '10.0.0.0/8' , '172.16.0.0/12' ] ) ;

    // 2. GDPR-anonymized IP for audit logs (last octet zeroed for IPv4, /48 for IPv6)
    $auditIp = truncateIpToSlash24( $clientIp ) ;

    // 3. Set-Cookie for the session
    $cookie = buildSetCookieHeader( 'session' , $token ,
    [
        CookieAttribute::HTTP_ONLY => true ,
        CookieAttribute::SECURE    => true ,
        CookieAttribute::SAME_SITE => SameSite::STRICT ,
        CookieAttribute::PATH      => '/' ,
        CookieAttribute::MAX_AGE   => 3600 ,
    ]) ;

    return [ 'ip' => $clientIp , 'auditIp' => $auditIp , 'cookie' => $cookie ] ;
}
```

## Mocking PSR-7 requests in your tests

The helpers under `helpers/ips/` accept a `Psr\Http\Message\ServerRequestInterface`. To test against synthetic headers, use any PSR-7 factory. The library's own test suite uses `Slim\Psr7\Factory\ServerRequestFactory` (declared in `require-dev`).

```php
use Slim\Psr7\Factory\ServerRequestFactory ;

$factory = new ServerRequestFactory() ;
$request = $factory->createServerRequest( 'GET' , '/api/me' )
                   ->withHeader( 'X-Forwarded-For' , '203.0.113.7, 198.51.100.10' )
                   ->withHeader( 'X-Real-IP' , '203.0.113.7' ) ;

$ip = getClientIp( $request , [ '10.0.0.0/8' ] ) ;
```

## Next steps

- [IP detection (ips/)](ips.md) — full catalog of the 11 IP helpers.
- [Cookies](cookies.md) — build and expire cookies with typed attributes.
