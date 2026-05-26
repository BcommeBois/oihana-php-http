<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Operating system family names emitted by
 * `oihana\http\helpers\detectUserAgentOs()` and stored in
 * `xyz\oihana\schema\http\UserAgentInfo::$os`.
 *
 * Centralises the vocabulary produced by the regex-based parser so
 * callers can `switch` / compare against constants instead of magic
 * strings. Same caveat as {@see BrowserName}: alternative parser
 * implementations may emit different spellings.
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class OsName
{
    use ConstantsTrait ;

    /**
     * Google Android (`Android <version>` token).
     */
    public const string ANDROID = 'Android' ;

    /**
     * Google ChromeOS (`CrOS` token).
     */
    public const string CHROME_OS = 'ChromeOS' ;

    /**
     * Apple iOS, on iPhone or iPod (`CPU iPhone OS <version>`).
     */
    public const string IOS = 'iOS' ;

    /**
     * Apple iPadOS, on iPad (`iPad; CPU OS <version>`). Reported
     * separately from {@see IOS} since iPadOS 13.
     */
    public const string IPADOS = 'iPadOS' ;

    /**
     * GNU/Linux distributions (any `Linux` token that does NOT also
     * carry an Android marker — the parser checks Android first).
     */
    public const string LINUX = 'Linux' ;

    /**
     * Apple macOS (`Mac OS X <version>` or bare `Macintosh` token).
     */
    public const string MACOS = 'macOS' ;

    /**
     * Microsoft Windows (`Windows NT <version>` token, remapped to
     * the marketing version by the parser — `10`, `8.1`, `7`, …).
     */
    public const string WINDOWS = 'Windows' ;
}
