<?php

namespace oihana\http\enums ;

use oihana\reflect\traits\ConstantsTrait ;

/**
 * Browser product names emitted by
 * `oihana\http\helpers\detectUserAgentBrowser()` and stored in
 * `xyz\oihana\schema\http\UserAgentInfo::$browser`.
 *
 * Centralises the vocabulary produced by the regex-based parser so
 * callers can `switch` / compare against constants instead of magic
 * strings.
 *
 * Casing matches the canonical product spelling carried by the
 * `User-Agent` header itself (`Chrome`, `Firefox`, `Safari`, …) for
 * recognisability — these are user-facing labels, not protocol
 * tokens.
 *
 * Different parser implementations (e.g. `ua-parser/uap-php`) may
 * emit different strings (`Mobile Safari` vs `Safari`). When pluging
 * an alternative parser, callers should map its output to this
 * vocabulary or accept that comparisons against these constants will
 * miss.
 *
 * @author  Marc Alcaraz
 * @package oihana\http\enums
 */
class BrowserName
{
    use ConstantsTrait ;

    /**
     * Google Chrome (and any Chromium-based browser that does NOT
     * advertise its own brand token — Brave, Arc, …).
     */
    public const string CHROME = 'Chrome' ;

    /**
     * Microsoft Edge (Chromium-based, `Edg/...` / `EdgA/...` /
     * `EdgiOS/...` tokens).
     */
    public const string EDGE = 'Edge' ;

    /**
     * Mozilla Firefox (and `FxiOS/...` on iOS).
     */
    public const string FIREFOX = 'Firefox' ;

    /**
     * Microsoft Internet Explorer (legacy, `MSIE` / `Trident` tokens).
     */
    public const string IE = 'IE' ;

    /**
     * Opera (Chromium-based `OPR/...` and legacy Presto `Opera/...`).
     */
    public const string OPERA = 'Opera' ;

    /**
     * Apple Safari (and `CriOS`/`FxiOS` siblings detected before
     * the Safari fallback).
     */
    public const string SAFARI = 'Safari' ;

    /**
     * Vivaldi browser (`Vivaldi/...` token).
     */
    public const string VIVALDI = 'Vivaldi' ;
}
