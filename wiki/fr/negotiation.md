# Négociation de contenu

Le dossier `helpers/negotiation/` couvre la négociation `Accept*` (RFC 7231 §5.3) et le parsing `Content-Type` (RFC 7231 §3.1.1.1). Les retours utilisent les enums `AcceptField` et `ContentTypeField` pour éviter les magic strings.

| Helper | À quoi ça sert |
|---|---|
| `parseAcceptHeader()` | Parse n'importe quel en-tête `Accept*` en entrées triées par q-value décroissante. |
| `negotiate()` | Choisit la valeur serveur qui satisfait le mieux un en-tête `Accept*` (wildcards, `q=0`, valeur par défaut). |
| `matchAcceptPattern()` | Teste un pattern déjà parsé contre un candidat (sous-helper de `negotiate`). |
| `parseContentType()` | Parse un en-tête `Content-Type` en type média + paramètres. |

## Parsing des en-têtes `Accept*`

### `parseAcceptHeader( string $header ) : array`

Parse **n'importe quel** en-tête de la famille `Accept` (Accept, Accept-Language, Accept-Encoding — syntaxe identique). Retourne une liste d'entrées triées par q-value décroissante (tri stable sur l'ordre du header).

```php
use function oihana\http\helpers\negotiation\parseAcceptHeader ;

use oihana\http\enums\AcceptField ;

$entries = parseAcceptHeader( 'text/html;q=0.9, application/json' ) ;

foreach ( $entries as $entry )
{
    $type    = $entry[ AcceptField::TYPE    ] ; // 'application/json' puis 'text/html'
    $quality = $entry[ AcceptField::QUALITY ] ; // 1.0 puis 0.9
    $params  = $entry[ AcceptField::PARAMS  ] ; // [] (autres paramètres)
}
```

Conventions :
- Le type est **lowercase**.
- q est `float` dans `[0.0, 1.0]` (clamp), défaut `1.0`, fallback `1.0` si non numérique.
- Les valeurs entre guillemets (`"…"`) sont déballées.
- `q=0` est conservé (refus explicite) et atterrit en queue.

### Pour `Accept-Language` et `Accept-Encoding`

Les trois en-têtes (`Accept`, `Accept-Language`, `Accept-Encoding`) partagent la même grammaire RFC 7231 §5.3 — `parseAcceptHeader()` est donc le point d'entrée unique. Le DTO retourné est identique pour les trois ; seul le sens conventionnel du champ `type` change (media type, tag de langue, ou nom d'encoding).

```php
parseAcceptHeader( 'fr-FR,fr;q=0.9,en;q=0.7' ) ;
// [
//   [ type => 'fr-fr' , quality => 1.0 , params => [] ] ,
//   [ type => 'fr'    , quality => 0.9 , params => [] ] ,
//   [ type => 'en'    , quality => 0.7 , params => [] ] ,
// ]

parseAcceptHeader( 'gzip, deflate, br;q=1.0' ) ;
// [ entries gzip, deflate, br ]
```

Pour `Accept-Language` : les tags sont lowercased (RFC 4647 §3.3.1, comparaison case-insensitive).

## Sélection du meilleur match

### `negotiate( string $accept , array $available , ?string $default = null ) : ?string`

Pour un en-tête `Accept*` et une liste de valeurs disponibles côté serveur, retourne celle qui satisfait le mieux le client. Gère les wildcards `*`, `*/*`, `type/*`. Saute les entrées `q=0`. La casse du candidat retenu est **préservée** dans le retour (`'Application/JSON'` reste `'Application/JSON'`).

```php
use function oihana\http\helpers\negotiation\negotiate ;

// Négociation de média
negotiate
(
    'text/html;q=0.9, application/json' ,
    [ 'application/json' , 'text/html' ] ,
) ;
// 'application/json' (q=1.0 bat q=0.9)

// Négociation de langue avec wildcard
negotiate
(
    'fr;q=0.9, *;q=0.5' ,
    [ 'en' , 'es' ] ,
    'en' ,
) ;
// 'en' (matché via `*`)

// Pas de match
negotiate
(
    'application/json' ,
    [ 'text/html' ] ,
    'text/html' ,
) ;
// 'text/html' (fallback sur $default)
```

### `matchAcceptPattern( string $pattern , string $candidate ) : bool`

Sous-helper public utilisé en interne par `negotiate`. Test 1-vers-1 d'un pattern (déjà parsé, lowercased) contre un candidat. Utile quand vous avez déjà parsé les entrées et que vous testez un candidat à la fois.

```php
matchAcceptPattern( 'text/*'      , 'text/plain' ) ;  // true
matchAcceptPattern( 'text/*'      , 'image/png'  ) ;  // false
matchAcceptPattern( 'text/html'   , 'TEXT/HTML'  ) ;  // true (case-insensitive)
matchAcceptPattern( '*/*'         , 'whatever'   ) ;  // true
```

## Parsing `Content-Type`

### `parseContentType( string $header ) : array`

Parse un `Content-Type` en tuple `{type, charset, boundary, params}`. Cas spéciaux extraits dans leurs propres clés ; tous les params restent disponibles dans `params`.

```php
use function oihana\http\helpers\negotiation\parseContentType ;

use oihana\http\enums\ContentTypeField ;

$parsed = parseContentType( 'text/html; charset=UTF-8' ) ;

$parsed[ ContentTypeField::TYPE     ] ; // 'text/html'
$parsed[ ContentTypeField::CHARSET  ] ; // 'utf-8'  (lowercasé)
$parsed[ ContentTypeField::BOUNDARY ] ; // null
$parsed[ ContentTypeField::PARAMS   ] ; // [ 'charset' => 'utf-8' ]

parseContentType( 'multipart/form-data; boundary="---WebKit"' ) ;
// boundary = '---WebKit'  (casse préservée — case-sensitive par RFC 2046)
```

Différences vs. native PHP :
- `type` et `charset` lowercasés (case-insensitive par RFC).
- `boundary` casse préservée (sensible à la casse pour multipart).
- Les valeurs entre guillemets sont déballées.

## Tableau des clés (enums)

### `AcceptField`

| Constante | Clé | Contenu |
|---|---|---|
| `TYPE` | `'type'` | média ou langue ou encoding, lowercased |
| `QUALITY` | `'quality'` | `float` dans `[0.0, 1.0]` |
| `PARAMS` | `'params'` | autres paramètres `array<string,string>` |

### `ContentTypeField`

| Constante | Clé | Contenu |
|---|---|---|
| `TYPE` | `'type'` | media-type, lowercased |
| `CHARSET` | `'charset'` | charset lowercased ou `null` |
| `BOUNDARY` | `'boundary'` | boundary multipart (casse préservée) ou `null` |
| `PARAMS` | `'params'` | tous les params `array<string,string>` |

## Choix de design

`parseAcceptHeader` garde l'ordre du header sur les ties de q-value (tri **stable**) — c'est une vue "brute" de ce que dit le client. Le tie-break par spécificité (`text/html` préféré à `*/*` à q=1) est le job de `negotiate`, pas du parser. Documenté volontairement comme ça.
