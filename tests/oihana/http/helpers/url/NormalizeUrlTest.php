<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\url\normalizeUrl ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\normalizeUrl()}.
 */
class NormalizeUrlTest extends TestCase
{
    public function testSchemeIsLowercased() :void
    {
        $this->assertSame
        (
            'https://example.com/' ,
            normalizeUrl( 'HTTPS://example.com/' ) ,
        ) ;
    }

    public function testHostIsLowercased() :void
    {
        $this->assertSame
        (
            'https://example.com/Path' ,
            normalizeUrl( 'https://Example.COM/Path' ) ,
        ) ;
    }

    public function testDefaultHttpPortIsDropped() :void
    {
        $this->assertSame
        (
            'http://example.com/' ,
            normalizeUrl( 'http://example.com:80/' ) ,
        ) ;
    }

    public function testDefaultHttpsPortIsDropped() :void
    {
        $this->assertSame
        (
            'https://example.com/' ,
            normalizeUrl( 'https://example.com:443/' ) ,
        ) ;
    }

    public function testNonDefaultPortIsPreserved() :void
    {
        $this->assertSame
        (
            'https://example.com:8443/' ,
            normalizeUrl( 'https://example.com:8443/' ) ,
        ) ;
    }

    public function testWebSocketDefaultPortsAreDropped() :void
    {
        $this->assertSame
        (
            'ws://example.com/' ,
            normalizeUrl( 'ws://example.com:80/' ) ,
        ) ;

        $this->assertSame
        (
            'wss://example.com/' ,
            normalizeUrl( 'wss://example.com:443/' ) ,
        ) ;
    }

    public function testQueryKeysAreSortedAlphabetically() :void
    {
        $this->assertSame
        (
            'https://example.com/?a=1&b=2&c=3' ,
            normalizeUrl( 'https://example.com/?c=3&a=1&b=2' ) ,
        ) ;
    }

    public function testDuplicateQueryKeysArePreservedInRelativeOrder() :void
    {
        // Multi-value keys keep the order of their values; only top-
        // level keys are sorted.
        $this->assertSame
        (
            'https://example.com/?a=1&a=2&b=3' ,
            normalizeUrl( 'https://example.com/?a=1&a=2&b=3' ) ,
        ) ;
    }

    public function testFragmentIsPreserved() :void
    {
        $this->assertSame
        (
            'https://example.com/path#section' ,
            normalizeUrl( 'HTTPS://Example.COM/path#section' ) ,
        ) ;
    }

    public function testRelativeUrlWithoutScheme() :void
    {
        $this->assertSame
        (
            '/api/v1?a=1&z=9' ,
            normalizeUrl( '/api/v1?z=9&a=1' ) ,
        ) ;
    }

    public function testUnparseableInputIsReturnedAsIs() :void
    {
        // `parse_url` returns false for a few pathological shapes —
        // the helper falls open in that case.
        $this->assertSame
        (
            'http:///' ,                       // empty host
            normalizeUrl( 'http:///' ) ,
        ) ;
    }

    public function testEmptyQueryIsNotEmittedWithQuestionMark() :void
    {
        $this->assertSame
        (
            'https://example.com/path' ,
            normalizeUrl( 'https://example.com/path' ) ,
        ) ;
    }

    public function testUserInfoIsPreserved() :void
    {
        $this->assertSame
        (
            'https://alice:secret@example.com/' ,
            normalizeUrl( 'https://alice:secret@Example.COM/' ) ,
        ) ;
    }
}
