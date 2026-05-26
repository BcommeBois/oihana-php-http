# Authorization

Le dossier `helpers/auth/` couvre le parsing de l'en-tête `Authorization` (RFC 7235) et les schémas `Bearer` (RFC 6750) et `Basic` (RFC 7617).

## Parser bas niveau

### `parseAuthorizationHeader( string $header ) : ?array`

Parse l'en-tête `Authorization` (ou `Proxy-Authorization`) en tuple `{scheme, credentials}`. Le scheme est normalisé à la casse canonique portée par `oihana\enums\http\AuthScheme` (de `oihana/php-enums`) — `'BEARER'`, `'bearer'`, `'BeArEr'` → tous remappés en `'Bearer'`. Les schemes inconnus passent inchangés. Les credentials sont retournés verbatim (paramètres Digest séparés par virgules préservés intacts).

```php
use function oihana\http\helpers\auth\parseAuthorizationHeader ;

use oihana\http\enums\AuthorizationField ;
use oihana\enums\http\AuthScheme ;

$parsed = parseAuthorizationHeader( 'Bearer eyJhbGci.eyJzdWIi.signed' ) ;

$parsed[ AuthorizationField::SCHEME      ] ; // AuthScheme::BEARER = 'Bearer'
$parsed[ AuthorizationField::CREDENTIALS ] ; // 'eyJhbGci.eyJzdWIi.signed'

parseAuthorizationHeader( '' ) ;             // null
```

## Helpers PSR-7

### `getBearerToken( ServerRequestInterface $request ) : ?string`

Extrait le token Bearer depuis une requête PSR-7. Retourne `null` quand le header est absent, le scheme n'est pas `Bearer`, ou les credentials sont vides.

```php
use function oihana\http\helpers\auth\getBearerToken ;

$token = getBearerToken( $request ) ;

if ( $token === null )
{
    return new Response( 401 ) ;
}

$claims = $jwt->decode( $token ) ;
```

### `getBasicAuth( ServerRequestInterface $request ) : ?array`

Extrait la paire username/password du schéma Basic (RFC 7617). Décode le base64, split sur le **premier** `:` (le mot de passe peut contenir des colons, RFC 7617 §2). Retourne `null` sur base64 invalide ou absence de `:`.

```php
use function oihana\http\helpers\auth\getBasicAuth ;
use oihana\http\enums\BasicAuthField ;

$creds = getBasicAuth( $request ) ;

if ( $creds === null )
{
    return new Response( 401 ) ;
}

$user = $creds[ BasicAuthField::USER ] ;
$pass = $creds[ BasicAuthField::PASS ] ;

// hash_equals pour comparer en temps constant
if ( $user !== $expectedUser
     || !hash_equals( $expectedHash , password_hash( $pass , PASSWORD_DEFAULT ) ) )
{
    return new Response( 401 ) ;
}
```

Cas légaux :
- Username et/ou password peuvent être **vides** — RFC 7617 le permet. `base64('user:')` retourne `[user => 'user', pass => '']`.
- Le password peut contenir des colons supplémentaires (`base64('user:my:complex:password')` → `pass = 'my:complex:password'`).

## Vocabulaires (enums)

### `AuthorizationField`

| Constante | Clé | Contenu |
|---|---|---|
| `SCHEME` | `'scheme'` | scheme normalisé via `AuthScheme` quand reconnu |
| `CREDENTIALS` | `'credentials'` | tout ce qui suit le premier whitespace (verbatim) |

### `BasicAuthField`

| Constante | Clé | Contenu |
|---|---|---|
| `USER` | `'user'` | partie avant le premier `:` |
| `PASS` | `'pass'` | partie après le premier `:` |

### `AuthScheme` (depuis `oihana/php-enums`)

Schemes couverts par le registre IANA : `BASIC`, `BEARER`, `DIGEST`, `HOBA`, `MUTUAL`, `NEGOTIATE`, `OAUTH`, `SCRAM_SHA_1`, `SCRAM_SHA_256`, `VAPID`. Réutilisés ici pour la comparaison case-insensitive.

## Schémas vendor-specific

Le parser **ne déballe pas** les enveloppes vendor-specific (`'sha256=<sig>'` de GitHub, `'t=…,v1=…'` de Stripe, `'v0=…:…'` de Slack…). Pour ces formats :
- Pour les webhooks signés HMAC, voir [Signatures](signatures.md) — l'helper `verifyHmacSignature()` traite le payload signature lui-même, vous strippez l'envelope d'abord.
- Pour les schémas Digest, OAuth1.0a, etc., parsez les `credentials` retournés en `key=value` via votre code maison.

## Voir aussi

- [Request helpers](request.md) — `wantsJson()`, `isAjax()`, `isHttpsRequest()` pour les autres inspections de requête.
- [Signatures HMAC](signatures.md) — vérification de webhooks et URLs signées.
