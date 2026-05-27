# Sécurité

`oihana/php-http` n'est pas un framework de sécurité — c'est une boîte à outils de primitives HTTP. Mais plusieurs de ces primitives portent des décisions secure-by-default qui méritent d'être documentées explicitement, à la fois pour que les consommateurs sachent ce qui est protégé et ce qui ne l'est pas, et pour que les défauts ne soient pas affaiblis par accident.

Cette page liste les fronts couverts, les pièges classiques à éviter, et ce qui sort explicitement du scope de la lib.

## Modèle de confiance trusted-proxy

Les helpers qui reconstituent une métadonnée client à partir d'en-têtes HTTP (`getClientIp()`, `isHttpsRequest()`) honorent **uniquement** les en-têtes `X-Forwarded-For`, `X-Forwarded-Proto`, `X-Real-IP` et `Forwarded` (RFC 7239) quand `REMOTE_ADDR` lui-même se trouve dans une **liste CIDR de proxies de confiance**. Si la liste est vide, ou si le proxy émetteur n'y figure pas, ces en-têtes sont ignorés et la lib retombe sur la connexion TCP directe.

```php
use function oihana\http\helpers\ips\getClientIp ;
use function oihana\http\helpers\request\isHttpsRequest ;

// Seuls les proxies dans ces CIDR peuvent influencer la décision.
$trustedProxies = [ '10.0.0.0/8' , '172.16.0.0/12' , '192.168.0.0/16' ] ;

$clientIp = getClientIp( $request , $trustedProxies ) ;
$secure   = isHttpsRequest( $request , $trustedProxies ) ;
```

**Piège classique.** Laisser la liste vide derrière un load-balancer **désactive** le walk de la chaîne `X-Forwarded-For` — le helper retournera l'IP du LB au lieu de celle du client. Inversement, lister `0.0.0.0/0` (ou ne pas filtrer du tout) **autorise n'importe quel attaquant** à injecter une IP arbitraire via un `X-Forwarded-For` forgé. Liste les CIDR précis de tes proxies, rien d'autre.

`getClientIp()` parcourt la chaîne `X-Forwarded-For` de droite à gauche en skippant les hops de confiance jusqu'à trouver le premier hop **non-trusted** — c'est lui le client réel. La logique est documentée en détail dans [`ips.md`](ips.md).

## Anonymisation RGPD

Trois helpers pour produire une IP anonymisée propre pour les journaux d'audit / observabilité :

- `truncateIpToSlash24()` — IPv4, masque le dernier octet (`203.0.113.42` → `203.0.113.0`).
- `truncateIpToSlash48()` — IPv6, masque les 80 derniers bits. Correspond à la profondeur recommandée par le BfDI/BSI allemand pour des logs serveur RGPD-friendly.
- `anonymizeIp()` — point d'entrée unifié qui route IPv4 → `/24` et IPv6 → `/48`, et passe le reste inchangé.

**Recommandation.** Dans un pipeline de logging ou d'audit, utilise `anonymizeIp()` comme point d'entrée unique — c'est la seule manière de garantir qu'aucune IP non-anonymisée ne finisse en clair dans un fichier de log.

## Validation des cookies

`buildSetCookieHeader()` rejette **avant émission** toute tentative d'injecter des caractères dangereux dans le nom ou la valeur :

- Nom : doit suivre la grammaire RFC 6265 / RFC 7230 (tokens ASCII, pas de séparateurs, pas de control characters).
- Valeur : rejette les ASCII control characters (incluant `\r` et `\n`) et `;`.

```php
buildSetCookieHeader( 'session\nInjected: header' , $value , 3600 , […] ) ;
// throws InvalidArgumentException — pas d'émission, l'attaque ne sort pas.
```

Les helpers `validateCookieName()` et `validateCookieValue()` sont exposés publiquement, de sorte que le code applicatif peut valider défensivement une donnée utilisateur **avant** de la passer à n'importe quel builder de cookie (pas seulement celui de cette lib).

**Recommandation.** Ne JAMAIS concaténer manuellement une donnée d'origine utilisateur dans un `Set-Cookie` : passer systématiquement par `buildSetCookieHeader()` ou valider explicitement avec `validateCookieValue()` avant.

## Comparaisons constant-time

Tous les helpers HMAC sous `helpers/signatures/` utilisent `hash_equals()` pour la comparaison finale — pas `===`, pas `strcmp()`. C'est la défense standard contre les attaques **timing side-channel** qui permettent à un attaquant de deviner une signature octet par octet en mesurant le temps de réponse.

Concrètement, dans `verifySignedUrl()` et `verifyHmacSignature()`, la comparaison entre le HMAC fourni par le client et celui que tu attends est toujours en temps constant. Tu n'as rien à configurer.

## URL canonicalization avant signature

`signUrl()` passe l'URL par `normalizeUrl()` **avant** de calculer la signature. La canonicalisation :

- Lowercase le scheme et le host.
- Drop les ports par défaut (`:80` pour http, `:443` pour https, etc.).
- Trie les clés de la query string par ordre alphabétique (les valeurs dupliquées gardent leur ordre relatif).

Conséquence : `?a=1&b=2` et `?b=2&a=1` produisent **la même signature**. Un attaquant qui ré-ordonne les paramètres d'une URL signée ne peut pas invalider la signature (et symétriquement, un client qui re-sérialize la query ne casse pas la vérification).

```php
use function oihana\http\helpers\signatures\signUrl ;
use function oihana\http\helpers\signatures\verifySignedUrl ;

$url = signUrl( 'https://api.example.com/files/42/download' , $secret , ttlSeconds: 300 ) ;

// 5 minutes de fenêtre avant que verifySignedUrl rejette.
if ( !verifySignedUrl( $url , $secret ) )
{
    return new Response( 401 ) ;
}
```

**Hors scope.** `normalizeUrl()` ne fait PAS de percent-decoding des caractères unreserved, ni de dot-segment resolution (`./`, `../`), ni de IDN/Punycode. Pour ces étages, traiter l'URL upstream.

## Base64url decode strict

Les signatures `signUrl()` / `verifySignedUrl()` encodent en **base64url** (RFC 4648 §5 — `-` et `_` au lieu de `+` et `/`, pas de padding). Le décodage utilise `oihana\core\encoding\base64UrlDecode()` depuis `oihana/php-core`, qui **rejette upfront** tout caractère hors de l'alphabet `[A-Za-z0-9_-]` via une regex avant même d'appeler `base64_decode()`.

Pourquoi : éviter de tolérer silencieusement des variantes URL-unsafe (`+` / `/` / whitespace / non-ASCII) qui pourraient causer des aberrations en aval — par exemple un attaquant qui envoie une signature avec un `+` à la place d'un espace pour exploiter une coercion `parse_str` côté serveur.

## Anti-CRLF dans le parsing

`parseAcceptHeader()`, `parseContentType()` et `parseForwardedHeader()` utilisent `oihana\core\strings\splitOutsideQuotes()` pour tokeniser leurs en-têtes. Cette fonction **respecte les régions quotées** : un `\r\n` ou un `,` à l'intérieur d'une chaîne quotée n'est pas traité comme un séparateur.

Pourquoi : défense en profondeur contre un en-tête malformé qui parviendrait à traverser une implémentation PSR-7 laxiste. Un attaquant qui fait passer `Accept: foo, "bar\r\nInjected: header", baz` ne peut pas faire apparaître `Injected: header` comme une entrée séparée du parsing.

## Ce qui n'est PAS couvert par cette lib

Quelques fronts importants de sécurité HTTP **ne sont pas dans le scope** de `oihana/php-http`. Ces préoccupations sont du ressort du middleware applicatif :

- **CSRF** — pas de helper CSRF. Utiliser `slim/csrf` ou équivalent au niveau middleware.
- **Rate-limiting** — pas couvert.
- **Security headers** (`Content-Security-Policy`, `Strict-Transport-Security`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `X-Content-Type-Options`, etc.) — pas couvert. Un futur paquet `oihana/php-http-middleware` est envisagé pour héberger ces helpers ; tracking interne au projet.
- **CORS** — pareil, niveau middleware, hors scope d'une lib de helpers procéduraux.
- **JWT / OAuth / OIDC authentication & token issuance** — `parseAuthorizationHeader()`, `getBearerToken()`, `getBasicAuth()` extraient et parsent les en-têtes uniquement. Pour valider/émettre des JWT ou orchestrer un flow OAuth/OIDC, voir `oihana/php-auth`.

## Baselines recommandées

### Cookie de session

```php
use function oihana\http\helpers\cookies\buildSetCookieHeader ;
use oihana\http\enums\CookieOption ;
use oihana\http\enums\SameSite ;
use oihana\http\enums\CookiePriority ;

$header = buildSetCookieHeader( 'session' , $token , 3600 ,
[
    CookieOption::SECURE      => true                 , // HTTPS uniquement
    CookieOption::HTTP_ONLY   => true                 , // pas d'accès JS
    CookieOption::SAME_SITE   => SameSite::STRICT     , // anti-CSRF de base
    CookieOption::PATH        => '/'                  ,
    CookieOption::PRIORITY    => CookiePriority::HIGH , // résistance à l'éviction
    CookieOption::PARTITIONED => true                  , // CHIPS — cookies cross-site partitionnés
]) ;
```

### URL signée avec TTL

```php
use function oihana\http\helpers\signatures\signUrl ;
use function oihana\http\helpers\signatures\verifySignedUrl ;

// Côté émission — TTL court pour un download privé.
$url = signUrl( 'https://api.example.com/files/42/download' , $secret , ttlSeconds: 300 ) ;

// Côté validation — un seul boolean fait la décision allow/deny.
if ( !verifySignedUrl( $url , $secret ) )
{
    return new Response( 401 ) ;
}
```

### Vérification webhook HMAC

```php
use function oihana\http\helpers\signatures\verifyHmacSignature ;

$payload   = (string) $request->getBody() ;
$signature = $request->getHeaderLine( 'X-Webhook-Signature' ) ;

if ( !verifyHmacSignature( $payload , $signature , $webhookSecret ) )
{
    return new Response( 401 ) ;
}
```

Pour Stripe (`Stripe-Signature: t=…,v1=…`), GitHub (`X-Hub-Signature-256: sha256=…`), Slack (`X-Slack-Signature: v0=…`) ou Mailchimp : **stripper l'envelope vendor avant** d'appeler `verifyHmacSignature()`. Le helper ne traite que la signature nue, intentionnellement — pas de couplage à un vendor spécifique.

## Voir aussi

- [`ips.md`](ips.md) — détails du walk `X-Forwarded-For` et du modèle de proxy de confiance.
- [`cookies.md`](cookies.md) — catalogue des attributs et de la validation.
- [`signatures.md`](signatures.md) — `signUrl`, `verifySignedUrl`, `verifyHmacSignature` en détail.
- [`request.md`](request.md) — `isHttpsRequest` et sa symétrie avec `getClientIp`.
