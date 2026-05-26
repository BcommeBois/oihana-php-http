<?php

namespace oihana\http\helpers\cookies ;

use DateMalformedStringException;
use DateTimeImmutable ;
use DateTimeInterface ;
use DateTimeZone ;
use InvalidArgumentException;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\CookieOption ;
use oihana\http\enums\CookiePriority ;
use oihana\http\enums\SameSite ;

use org\common\DateFormat ;

/**
 * Builds a `Set-Cookie` HTTP header value.
 *
 * Reasonable defaults are applied so the helper is safe to use
 * for session/auth cookies without specifying every attribute:
 *
 * - `Path=/`
 * - `HttpOnly`
 * - `SameSite=Lax`
 * - no `Domain` attribute
 * - no `Secure` attribute
 *
 * Override any default via the `$options` array, keyed with
 * `oihana\http\enums\CookieOption` constants.
 *
 * Example:
 * ```php
 * use function oihana\http\helpers\cookies\buildSetCookieHeader ;
 * use oihana\http\enums\CookieOption ;
 *
 * $header = buildSetCookieHeader
 * (
 *     'access_token' ,
 *     $token ,
 *     3600 ,
 *     [
 *         CookieOption::DOMAIN => 'example.com' ,
 *         CookieOption::SECURE => true ,
 *     ]
 * ) ;
 * ```
 *
 * @param string      $name    Cookie name.
 * @param string|null $value   Cookie value. `null` is rendered as
 *                             an empty value (suitable for
 *                             clearing cookies — see also
 *                             `expireSetCookieHeader`).
 * @param int         $maxAge  Cookie lifetime in seconds. Use `0`
 *                             to expire the cookie immediately.
 * @param array{
 *     domain?:      string ,
 *     expires?:     int|string|DateTimeInterface|null ,
 *     secure?:      bool   ,
 *     path?:        string ,
 *     sameSite?:    string ,
 *     httpOnly?:    bool   ,
 *     priority?:    string|null ,
 *     partitioned?: bool
 * } $options Optional attribute overrides keyed with
 *           `CookieOption` constants:
 *           - `CookieOption::DOMAIN`      (string, default `''`)
 *           - `CookieOption::EXPIRES`     (int|string|DateTimeInterface|null,
 *                                          default `null` — attribute is
 *                                          skipped when null or absent)
 *           - `CookieOption::SECURE`      (bool, default `false`)
 *           - `CookieOption::PATH`        (string, default `'/'`)
 *           - `CookieOption::SAME_SITE`   (string, default `SameSite::LAX`)
 *           - `CookieOption::HTTP_ONLY`   (bool, default `true`)
 *           - `CookieOption::PRIORITY`    (string|null, default `null` —
 *                                          one of the `CookiePriority`
 *                                          constants when set)
 *           - `CookieOption::PARTITIONED` (bool, default `false`)
 *
 * @return string The full `Set-Cookie` header value.
 *
 * @throws InvalidArgumentException When `$name` is not a valid RFC 7230
 *                                   token, when `$value` contains an
 *                                   ASCII control character or `;`, when
 *                                   `Expires` is of an unsupported type,
 *                                   or when `Priority` is not one of the
 *                                   `CookiePriority` constants.
 *                                   See {@see validateCookieName()} and
 *                                   {@see validateCookieValue()}.
 */
function buildSetCookieHeader( string $name , ?string $value , int $maxAge , array $options = [] ) :string
{
    validateCookieName( $name ) ;

    if ( $value !== null )
    {
        validateCookieValue( $value ) ;
    }

    $domain      = $options[ CookieOption::DOMAIN      ] ?? ''             ;
    $expires     = $options[ CookieOption::EXPIRES     ] ?? null           ;
    $secure      = $options[ CookieOption::SECURE      ] ?? false          ;
    $path        = $options[ CookieOption::PATH        ] ?? '/'            ;
    $sameSite    = $options[ CookieOption::SAME_SITE   ] ?? SameSite::LAX  ;
    $httpOnly    = $options[ CookieOption::HTTP_ONLY   ] ?? true           ;
    $priority    = $options[ CookieOption::PRIORITY    ] ?? null           ;
    $partitioned = $options[ CookieOption::PARTITIONED ] ?? false          ;

    $parts =
    [
        "$name=$value" ,
        CookieAttribute::PATH      . "=$path"     ,
        CookieAttribute::MAX_AGE   . "=$maxAge"   ,
        CookieAttribute::SAME_SITE . "=$sameSite" ,
    ] ;

    if( $httpOnly )
    {
        $parts[] = CookieAttribute::HTTP_ONLY ;
    }

    if( $secure )
    {
        $parts[] = CookieAttribute::SECURE ;
    }

    if( $domain )
    {
        $parts[] = CookieAttribute::DOMAIN . "=$domain" ;
    }

    if( $expires !== null )
    {
        $parts[] = CookieAttribute::EXPIRES . '=' . formatCookieExpires( $expires ) ;
    }

    if( $priority !== null )
    {
        if( !CookiePriority::includes( $priority ) )
        {
            throw new InvalidArgumentException
            (
                sprintf
                (
                    'Cookie Priority "%s" is not one of the CookiePriority constants (Low|Medium|High).' ,
                    $priority ,
                )
            ) ;
        }
        $parts[] = CookieAttribute::PRIORITY . "=$priority" ;
    }

    if( $partitioned )
    {
        $parts[] = CookieAttribute::PARTITIONED ;
    }

    return implode( '; ' , $parts ) ;
}

/**
 * Normalises an `Expires` option value into an RFC 7231 IMF-fixdate string.
 *
 * Accepts:
 * - `int`               — interpreted as a Unix timestamp ;
 * - `DateTimeInterface` — converted to UTC ;
 * - `string`            — passed through verbatim (escape hatch).
 *
 * Internal helper. Not part of the public API.
 *
 * @param int|string|DateTimeInterface $value
 *
 * @return string
 *
 * @throws DateMalformedStringException
 *
 * @internal
 */
function formatCookieExpires( int|string|DateTimeInterface $value ) :string
{
    if ( is_string( $value ) )
    {
        return $value ;
    }

    if ( is_int( $value ) )
    {
        $dt = new DateTimeImmutable( '@' . $value )->setTimezone( new DateTimeZone( 'UTC' ) ) ;
    }
    else
    {
        $dt = ( $value instanceof DateTimeImmutable
            ? $value
            : DateTimeImmutable::createFromInterface( $value ) )
            ->setTimezone( new DateTimeZone( 'UTC' ) ) ;
    }

    return $dt->format( DateFormat::RFC7231 ) ;
}
