# Helpers de requête PSR-7

Le dossier `helpers/request/` contient trois prédicats pour inspecter rapidement une requête PSR-7 sans réécrire la même logique dans chaque controller.

| Helper | À quoi ça sert |
|---|---|
| `wantsJson()` | Indique si le client préfère du JSON (entrée `Accept` prioritaire ; `/json` & `+json`). |
| `isAjax()` | Vérifie `X-Requested-With: XMLHttpRequest` (insensible à la casse). |
| `isHttpsRequest()` | Indique si la requête est en HTTPS, directement ou via un proxy de confiance (`X-Forwarded-Proto`). |

## `wantsJson( ServerRequestInterface $request ) : bool`

Tells si le client préfère une réponse JSON, en inspectant le **top-priority** entry de l'en-tête `Accept`. Reconnaît `/json` (`application/json`, `text/json`) et `+json` (`application/ld+json`, `application/vnd.api+json`, …) — l'heuristique Laravel-style.

```php
use function oihana\http\helpers\request\wantsJson ;

// Accept: application/json, text/html;q=0.9
wantsJson( $request ) ; // true

// Accept: text/html
wantsJson( $request ) ; // false

// Accept absent
wantsJson( $request ) ; // false
```

`X-Requested-With` n'est **pas** considéré ici. Trop de libs le posent pour des requêtes AJAX qui veulent du HTML fragmenté, pas du JSON. Pour ce signal, voir `isAjax()`.

## `isAjax( ServerRequestInterface $request ) : bool`

Check case-insensitive de `X-Requested-With: XMLHttpRequest`.

```php
use function oihana\http\helpers\request\isAjax ;

if ( isAjax( $request ) )
{
    // Retour fragmenté HTML, sans layout complet
}
```

Caveats :
- jQuery, Axios en mode legacy, et la plupart des libs AJAX anciennes posent ce header automatiquement.
- `fetch()` ne le pose **pas** par défaut — le caller doit l'ajouter explicitement. Les SPA modernes peuvent donc apparaître non-AJAX à ce helper même si elles le sont.
- Distinct de `wantsJson()` — être AJAX n'implique pas vouloir du JSON (fragments HTML over AJAX très répandus).

## `isHttpsRequest( ServerRequestInterface $request , array $trustedProxies = [] ) : bool`

Tells si la requête est en HTTPS, soit directement, soit via un reverse proxy de confiance.

Ordre de résolution :
1. Check direct du scheme via `Psr\Http\Message\UriInterface::getScheme()` — retourne `true` immédiatement si `'https'`.
2. Mode trusted-proxy : si `$trustedProxies` est fourni **et** `REMOTE_ADDR` est dans la liste, lecture de `X-Forwarded-Proto`. Retourne `true` quand il vaut `https` (case-insensitive).

Sémantique anti-spoofing **symétrique** à `getClientIp()` : `X-Forwarded-Proto` n'est honoré que quand le hop direct est lui-même de confiance. Avec une liste `$trustedProxies` vide, le header forwarded est ignoré et la fonction retourne `false` quand le scheme direct n'est pas HTTPS.

```php
use function oihana\http\helpers\request\isHttpsRequest ;

// Direct HTTPS
isHttpsRequest( $request ) ; // true

// Derrière Cloudflare avec un CIDR de confiance
isHttpsRequest( $request , [ '173.245.48.0/20' , '...' ] ) ; // true

// Header forwarded d'une source non-fiable — refusé
isHttpsRequest( $request ) ; // false (REMOTE_ADDR pas dans $trustedProxies)
```

### Pourquoi ne pas faire confiance à `X-Forwarded-Proto` aveuglément ?

Un client malveillant peut envoyer `X-Forwarded-Proto: https` à votre serveur si vous ne filtrez pas. Si vous utilisez `isHttpsRequest()` pour décider d'émettre des cookies `Secure`, un mauvais filtrage permet à l'attaquant de récupérer ces cookies par HTTP. Le filtrage par `$trustedProxies` symétrise la logique avec `getClientIp()` : un seul modèle de confiance pour les deux.

## Combinaison typique

Dans un middleware d'API qui négocie le format de réponse :

```php
use function oihana\http\helpers\request\isAjax ;
use function oihana\http\helpers\request\wantsJson ;

if ( wantsJson( $request ) )
{
    return $this->json( $data ) ;        // Accept négocié → JSON
}

if ( isAjax( $request ) )
{
    return $this->htmlFragment( $data ) ; // AJAX sans Accept JSON → fragment HTML
}

return $this->htmlPage( $data ) ;         // Navigation classique
```

## Voir aussi

- [Détection d'IP](ips.md) — `getClientIp()` partage le même modèle de trusted-proxy que `isHttpsRequest()`.
- [Négociation de contenu](negotiation.md) — `wantsJson()` repose sur `parseAcceptHeader()`.
- [Authorization](authorization.md) — `getBearerToken()`, `getBasicAuth()`.
