# PSR-7 request helpers

The `helpers/request/` folder contains three predicates that inspect a PSR-7 request quickly without rewriting the same logic in every controller.

## `wantsJson( ServerRequestInterface $request ) : bool`

Tells whether the client prefers a JSON response by inspecting the **top-priority** entry of the `Accept` header. Recognises `/json` (`application/json`, `text/json`) and `+json` (`application/ld+json`, `application/vnd.api+json`, …) — Laravel-style heuristic.

```php
use function oihana\http\helpers\request\wantsJson ;

// Accept: application/json, text/html;q=0.9
wantsJson( $request ) ; // true

// Accept: text/html
wantsJson( $request ) ; // false

// No Accept header
wantsJson( $request ) ; // false
```

`X-Requested-With` is **not** considered here. Too many libs set it for AJAX requests that want HTML fragments, not JSON. For that signal, see `isAjax()`.

## `isAjax( ServerRequestInterface $request ) : bool`

Case-insensitive check of `X-Requested-With: XMLHttpRequest`.

```php
use function oihana\http\helpers\request\isAjax ;

if ( isAjax( $request ) )
{
    // Return an HTML fragment without the full layout
}
```

Caveats:
- jQuery, Axios in legacy mode and most older AJAX libs set this header automatically.
- `fetch()` does **not** set it by default — the caller must add it explicitly. Modern SPAs may therefore look non-AJAX to this helper even when they are.
- Distinct from `wantsJson()` — being AJAX does not imply wanting JSON (HTML fragments over AJAX are common).

## `isHttpsRequest( ServerRequestInterface $request , array $trustedProxies = [] ) : bool`

Tells whether the request was made over HTTPS, either directly or through a trusted reverse proxy.

Resolution order:
1. Direct scheme check via `Psr\Http\Message\UriInterface::getScheme()` — returns `true` immediately when it is `'https'`.
2. Trusted-proxy mode: when `$trustedProxies` is provided **and** `REMOTE_ADDR` is in the list, reads `X-Forwarded-Proto`. Returns `true` when it equals `https` (case-insensitive).

Anti-spoofing semantics **symmetric** with `getClientIp()`: `X-Forwarded-Proto` is only honoured when the direct hop is itself trusted. With an empty `$trustedProxies`, the forwarded header is ignored and the function returns `false` when the direct scheme is not HTTPS.

```php
use function oihana\http\helpers\request\isHttpsRequest ;

// Direct HTTPS
isHttpsRequest( $request ) ; // true

// Behind Cloudflare with a trusted CIDR
isHttpsRequest( $request , [ '173.245.48.0/20' , '...' ] ) ; // true

// Forwarded header from an untrusted source — refused
isHttpsRequest( $request ) ; // false (REMOTE_ADDR not in $trustedProxies)
```

### Why not trust `X-Forwarded-Proto` blindly?

A malicious client can send `X-Forwarded-Proto: https` to your server if you don't filter. If you use `isHttpsRequest()` to decide whether to emit `Secure` cookies, bad filtering lets the attacker collect those cookies over HTTP. The `$trustedProxies` filter makes the trust model symmetric with `getClientIp()`: one trust model for both.

## Typical combination

In an API middleware that negotiates response format:

```php
use function oihana\http\helpers\request\isAjax ;
use function oihana\http\helpers\request\wantsJson ;

if ( wantsJson( $request ) )
{
    return $this->json( $data ) ;         // Accept-negotiated JSON
}

if ( isAjax( $request ) )
{
    return $this->htmlFragment( $data ) ; // AJAX without JSON Accept → HTML fragment
}

return $this->htmlPage( $data ) ;         // Classic navigation
```

## See also

- [IP detection](ips.md) — `getClientIp()` shares the same trusted-proxy model as `isHttpsRequest()`.
- [Content negotiation](negotiation.md) — `wantsJson()` is built on `parseAcceptHeader()`.
- [Authorization](authorization.md) — `getBearerToken()`, `getBasicAuth()`.
