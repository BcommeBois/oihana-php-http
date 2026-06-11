<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\parseForwardedHeader;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\parseForwardedHeader()}.
 */
class ParseForwardedHeaderTest extends TestCase
{
    public function testEmptyHeaderReturnsEmptyList(): void
    {
        $this->assertSame( [] , parseForwardedHeader( ''     ) ) ;
        $this->assertSame( [] , parseForwardedHeader( '   '  ) ) ;
    }

    public function testSingleForEntryWithExtraParameters(): void
    {
        $this->assertSame
        (
            [ '192.0.2.60' ] ,
            parseForwardedHeader( 'for=192.0.2.60;proto=http;by=203.0.113.43' )
        ) ;
    }

    public function testMultipleForEntries(): void
    {
        $this->assertSame
        (
            [ '192.0.2.43' , '198.51.100.17' ] ,
            parseForwardedHeader( 'for=192.0.2.43, for=198.51.100.17' )
        ) ;
    }

    public function testQuotedIPv6WithPort(): void
    {
        $this->assertSame
        (
            [ '2001:db8:cafe::17' ] ,
            parseForwardedHeader( 'for="[2001:db8:cafe::17]:4711"' )
        ) ;
    }

    public function testIPv4WithPort(): void
    {
        $this->assertSame
        (
            [ '192.0.2.43' ] ,
            parseForwardedHeader( 'for="192.0.2.43:47011"' )
        ) ;
    }

    public function testMixedIPv4AndIPv6(): void
    {
        $this->assertSame
        (
            [ '2001:db8::1' , '203.0.113.43' ] ,
            parseForwardedHeader( 'for="[2001:db8::1]:4711", for=203.0.113.43' )
        ) ;
    }

    public function testObfuscatedIdentifiersAreSkipped(): void
    {
        $this->assertSame
        (
            [ '192.0.2.60' ] ,
            parseForwardedHeader( 'for=_hidden, for=192.0.2.60, for=unknown' )
        ) ;
    }

    public function testKeyIsCaseInsensitive(): void
    {
        $this->assertSame
        (
            [ '192.0.2.60' ] ,
            parseForwardedHeader( 'For=192.0.2.60' )
        ) ;
    }

    public function testNonForKeysAreIgnored(): void
    {
        $this->assertSame
        (
            [] ,
            parseForwardedHeader( 'by=203.0.113.43;proto=http;host=example.com' )
        ) ;
    }

    public function testMalformedSegmentsAreSkipped(): void
    {
        $this->assertSame
        (
            [ '192.0.2.60' ] ,
            parseForwardedHeader( 'garbage,for=192.0.2.60,;,for=' )
        ) ;
    }

    public function testBracketedIPv6WithoutClosingBracketIsSkipped(): void
    {
        $this->assertSame
        (
            [] ,
            parseForwardedHeader( 'for="[2001:db8::1"' )
        ) ;
    }

    public function testEmptyBracketedValueIsSkipped(): void
    {
        $this->assertSame
        (
            [] ,
            parseForwardedHeader( 'for="[]"' )
        ) ;
    }

    public function testInvalidIPsAreSkipped(): void
    {
        $this->assertSame
        (
            [ '192.0.2.60' ] ,
            parseForwardedHeader( 'for=999.999.999.999, for=192.0.2.60' )
        ) ;
    }
}
