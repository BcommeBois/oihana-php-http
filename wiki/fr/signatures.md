# Signatures HMAC

Le dossier `helpers/signatures/` couvre les deux patterns récurrents de vérification de signature HTTP :

1. **URLs pré-signées** — liens temporaires non-falsifiables (download S3-style, password reset, magic login, partage temporaire).
2. **Webhooks signés** — vérification de payload Stripe / GitHub / Slack / Mailchimp.

Toutes les comparaisons utilisent `hash_equals()` — temps constant, safe contre les attaques timing-side-channel.

## URLs signées

### `signUrl( string $url , string $secret , ?int $ttlSeconds = null , string $algo = 'sha256' ) : string`

Signe une URL avec un HMAC et ajoute `?sig=<base64url>` (et `&exp=<timestamp>` si TTL fourni).

```php
use function oihana\http\helpers\signatures\signUrl ;

$url = signUrl
(
    'https://api.example.com/files/42?download=1' ,
    $secret ,
    ttlSeconds: 600 ,  // expire dans 10 minutes
) ;
// https://api.example.com/files/42?download=1&exp=1767225600&sig=…
```

Caractéristiques :
- L'URL est **canonicalisée** avant signature via `normalizeUrl()` (clés query triées, scheme/host lowercased, port par défaut supprimé) — l'ordre des params n'affecte pas la signature.
- Le helper est **idempotent** : les `sig` / `exp` existants sont strippés avant la nouvelle signature, donc re-signer une URL déjà signée fonctionne.
- Base64url **sans padding** dans le `sig` — URL-safe sans `+`, `/`, `=`.
- Throws `InvalidArgumentException` sur secret vide / algo inconnu / URL non-parseable (fail fast).

### `verifySignedUrl( string $url , string $secret , string $algo = 'sha256' ) : bool`

Inverse symétrique. Retourne `true` quand la signature est valide ET (si `exp` est présent) l'URL n'a pas expiré.

```php
use function oihana\http\helpers\signatures\verifySignedUrl ;

if ( !verifySignedUrl( $signedUrl , $secret ) )
{
    return new Response( 401 ) ;
}

// …servir la ressource signée
```

Fail-closed : retourne **toujours** `false` (jamais d'exception) sur tous les modes d'échec — `sig` manquant, `exp` expiré, base64url mal formé, signature mismatch, URL non parseable. Le caller peut utiliser le boolean comme **unique** signal allow/deny.

L'algorithme `$algo` doit matcher celui passé à `signUrl()`. Le défaut `sha256` est cohérent entre les deux helpers.

### Garanties anti-tampering

```php
// URL signée
$signed = signUrl( 'https://api.example.com/file?download=1' , $secret , 600 ) ;
verifySignedUrl( $signed , $secret ) ;             // true

// Tamper du query — rejeté
$tampered = str_replace( 'download=1' , 'download=0' , $signed ) ;
verifySignedUrl( $tampered , $secret ) ;           // false

// Tamper du path — rejeté
$tampered = str_replace( '/file' , '/admin' , $signed ) ;
verifySignedUrl( $tampered , $secret ) ;           // false
```

## Webhooks (payload signing)

### `verifyHmacSignature( string $payload , string $signature , string $secret , string $algo = 'sha256' , string $format = 'hex' ) : bool`

Vérifie un HMAC contre un payload brut — la primitive cryptographique des webhooks.

```php
use function oihana\http\helpers\signatures\verifyHmacSignature ;

$payload = (string) $request->getBody() ;
$header  = $request->getHeaderLine( 'X-Hub-Signature-256' ) ;
// 'sha256=abcdef…' — strippez le préfixe vendor
$sig = substr( $header , strlen( 'sha256=' ) ) ;

if ( !verifyHmacSignature( $payload , $sig , $secret ) )
{
    return new Response( 401 ) ;
}
```

Le paramètre `$format` doit matcher l'encodage du sender :

| Valeur | Encodage | Vendeurs typiques |
|---|---|---|
| `'hex'` (défaut) | hexadécimal lowercased | GitHub, Slack, Mailchimp |
| `'base64'` | base64 standard | webhooks Plaid, certains custom |
| `'base64url'` | base64url RFC 4648 §5 | JWT-style, JWS detached |

Pas géré ici (**intentionnellement**) :
- Stripe `Stripe-Signature: t=…,v1=…` — strippez le timestamp et le préfixe `v1=` d'abord.
- GitHub `X-Hub-Signature-256: sha256=…` — strippez le préfixe `sha256=` d'abord.
- Slack `X-Slack-Signature: v0=…` + `X-Slack-Request-Timestamp` — strippez `v0=` et concaténez le timestamp dans le payload selon leur recette.

Le helper se concentre sur la primitive crypto (compute HMAC, compare en temps constant). Le framing vendor-specific reste de la responsabilité de l'intégration.

## Constantes typées

Évitez les chaînes brutes — le vocabulaire de signature est exposé en enums typées dans `oihana\http\enums` :

- `SignatureFormat::HEX` / `::BASE64` / `::BASE64URL` — le `$format` accepté par `verifyHmacSignature()`.
- `SignedUrlField::SIGNATURE` (`'sig'`) / `::EXPIRY` (`'exp'`) — les noms de paramètres de query écrits par `signUrl()` et lus par `verifySignedUrl()`. Pratique quand vous inspectez vous-même une URL signée.

```php
use oihana\http\enums\SignatureFormat ;

verifyHmacSignature( $payload , $sig , $secret , 'sha256' , SignatureFormat::BASE64 ) ;
```

## Choix techniques

- `hash_equals()` partout — comparaisons en temps constant.
- `base64UrlEncode` / `base64UrlDecode` de `oihana\core\encoding` (paquet `oihana/php-core`) — encodage URL-safe sans padding, validation stricte de l'alphabet en lecture.
- L'algorithme `sha256` par défaut couvre le besoin courant. `sha512` recommandé pour les enjeux long terme. `sha1` toléré seulement pour compat ascendant avec des webhooks legacy — préférez 256+ pour le nouveau code.

## Voir aussi

- [URL](urls.md) — `signUrl()` utilise `normalizeUrl()` en interne.
- [Authorization](authorization.md) — pour `Bearer` JWT et `Basic`.
