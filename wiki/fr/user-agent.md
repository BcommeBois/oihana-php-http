# User-Agent

Le dossier `helpers/` contient un parseur regex-based, sans dépendance externe, qui transforme une chaîne `User-Agent` en DTO structuré. Conçu pour le 95% du trafic web courant (Chrome, Firefox, Safari, Edge, Opera, Vivaldi, IE ; Windows, macOS, Linux, Android, iOS, iPadOS, ChromeOS ; les principaux bots).

Pour une couverture exhaustive du long tail (bots régionaux, navigateurs rares, *device fingerprinting*), branchez `ua-parser/uap-php` au-dessus — la lib est volontairement dependency-free.

| Helper | À quoi ça sert |
|---|---|
| `getUserAgent()` | Lit la chaîne `User-Agent` brute depuis `$_SERVER` (`null` si absente). |
| `parseUserAgent()` | Parse une chaîne UA en DTO structuré `UserAgentInfo` (renvoie toujours une instance). |
| `isBotUserAgent()` | Indique si un UA est un bot / crawler / outil HTTP. |
| `isMobileUserAgent()` | Indique si un UA est un format mobile **ou** tablette. |
| *4 détecteurs bas-niveau* | Un signal à la fois (`detectUserAgentBot` / `Browser` / `Os` / `DeviceType`) — voir [Détecteurs bas-niveau](#détecteurs-bas-niveau). |

## Lecture brute

### `getUserAgent() : ?string`

Lit la chaîne `User-Agent` depuis `$_SERVER`. Retourne `null` quand le header est absent.

```php
use function oihana\http\helpers\getUserAgent ;

$raw = getUserAgent() ;
// 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) ...'
```

## Parsing structuré

### `parseUserAgent( ?string $ua ) : UserAgentInfo`

Orchestrateur. Retourne un `xyz\oihana\schema\http\UserAgentInfo` (DTO du paquet `oihana/php-schema`) avec les champs `browser`, `browserVersion`, `os`, `osVersion`, `deviceType`, `isBot`, `raw`. Toujours retourne une instance — jamais `null` — les champs absents sont exposés à `null` dans le DTO.

```php
use function oihana\http\helpers\parseUserAgent ;

$info = parseUserAgent
(
    'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) '
    . 'AppleWebKit/605.1.15 (KHTML, like Gecko) '
    . 'Version/17.5 Safari/605.1.15'
) ;

$info->browser        ; // 'Safari'
$info->browserVersion ; // '17.5'
$info->os             ; // 'macOS'
$info->osVersion      ; // '14.5'
$info->deviceType     ; // 'desktop'
$info->isBot          ; // false
$info->raw            ; // chaîne UA préservée verbatim (audit/forensique)
```

## Détecteurs bas-niveau

Les quatre détecteurs sont **publics** : les consommateurs qui n'ont besoin que d'un signal peuvent éviter le parse complet.

| Helper | Signature | Retour |
|---|---|---|
| `detectUserAgentBot( string $ua )` | bool | `true` si bot / crawler / outil HTTP |
| `detectUserAgentBrowser( string $ua )` | `array{0:?string,1:?string}` | `[name, version]`, `name` = constante `BrowserName` ou `null` |
| `detectUserAgentOs( string $ua )` | `array{0:?string,1:?string}` | `[name, version]`, `name` = constante `OsName` ou `null` |
| `detectUserAgentDeviceType( string $ua , ?string $os )` | string | constante `DeviceType` |

## Prédicats utilitaires

### `isBotUserAgent( ?string $ua ) : bool`

Wrapper léger autour de `parseUserAgent`. Accepte `null` ou empty (retourne `false`). Pratique pour rate-limiting, filtrage d'audit, paywalls soft.

```php
use function oihana\http\helpers\isBotUserAgent ;

if ( isBotUserAgent( $request->getHeaderLine( 'User-Agent' ) ) )
{
    return new Response( 429 ) ;
}
```

### `isMobileUserAgent( ?string $ua ) : bool`

Retourne `true` pour les form factors **mobile ET tablette** — convention populaire (`Mobile_Detect` et consorts) : la question typique "dois-je servir une UI mobile ?" groupe les tablettes avec les téléphones.

Si vous voulez strictement les téléphones (sans tablette), passez par `parseUserAgent($ua)->deviceType === DeviceType::MOBILE`.

## Vocabulaires émis

### `oihana\http\enums\BrowserName`

| Constante | Valeur | Couvre |
|---|---|---|
| `CHROME` | `'Chrome'` | Chrome + Chromium-based sans brand token |
| `EDGE` | `'Edge'` | Microsoft Edge (`Edg/`, `EdgA/`, `EdgiOS/`) |
| `FIREFOX` | `'Firefox'` | Firefox + Firefox iOS (`FxiOS/`) |
| `IE` | `'IE'` | Legacy `MSIE` / `Trident` |
| `OPERA` | `'Opera'` | `OPR/` + legacy Presto |
| `SAFARI` | `'Safari'` | Safari (détecté en dernier, fallback `Safari/`) |
| `VIVALDI` | `'Vivaldi'` | Vivaldi |

### `oihana\http\enums\OsName`

| Constante | Valeur | Couvre |
|---|---|---|
| `ANDROID` | `'Android'` | `Android <version>` |
| `CHROME_OS` | `'ChromeOS'` | `CrOS` |
| `IOS` | `'iOS'` | iPhone, iPod |
| `IPADOS` | `'iPadOS'` | iPad (reporté séparément depuis iPadOS 13) |
| `LINUX` | `'Linux'` | distributions GNU/Linux (Android filtré avant) |
| `MACOS` | `'macOS'` | `Mac OS X` + bare `Macintosh` |
| `WINDOWS` | `'Windows'` | `Windows NT <ver>` remappé en marketing version (`10`, `8.1`, `7`, …) |

### `xyz\oihana\schema\constants\http\DeviceType`

Classification grossière du device. Constantes : `BOT`, `DESKTOP`, `MOBILE`, `TABLET`, `UNKNOWN`. Vocabulaire stable, défini dans le paquet schema pour pouvoir être consommé par d'autres outils (audit, sessions, analytics).

## DTO `UserAgentInfo`

Vit dans `oihana/php-schema` sous `xyz\oihana\schema\http\UserAgentInfo` (étend `Intangible` Schema.org). Pratique pour embarquer dans un enregistrement `Session` ou `AuditAction` qui veut conserver la décomposition structurée du UA en plus du raw.

## Limite documentée

iPadOS récent reporte un UA macOS-like avec un token `Macintosh` et **aucun** hint `iPad` — ces iPads seront classifiés comme `desktop`. Les Client-Hints (`Sec-CH-UA`, `Sec-CH-UA-Mobile`) sont le seul signal fiable pour cette désambiguïsation.
