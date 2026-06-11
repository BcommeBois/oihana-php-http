<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\url\parseQueryString ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\parseQueryString()}.
 */
class ParseQueryStringTest extends TestCase
{
    public function testEmptyReturnsEmpty() :void
    {
        $this->assertSame( [] , parseQueryString( '' ) ) ;
    }

    public function testJustQuestionMarkReturnsEmpty() :void
    {
        $this->assertSame( [] , parseQueryString( '?' ) ) ;
    }

    public function testLeadingQuestionMarkIsStripped() :void
    {
        $this->assertSame
        (
            [ 'a' => [ '1' ] ] ,
            parseQueryString( '?a=1' ) ,
        ) ;
    }

    public function testSinglePair() :void
    {
        $this->assertSame
        (
            [ 'a' => [ '1' ] ] ,
            parseQueryString( 'a=1' ) ,
        ) ;
    }

    public function testMultiplePairs() :void
    {
        $this->assertSame
        (
            [ 'a' => [ '1' ] , 'b' => [ '2' ] ] ,
            parseQueryString( 'a=1&b=2' ) ,
        ) ;
    }

    public function testDuplicateKeysArePreservedAsList() :void
    {
        $this->assertSame
        (
            [ 'a' => [ '1' , '2' , '3' ] ] ,
            parseQueryString( 'a=1&a=2&a=3' ) ,
        ) ;
    }

    public function testValueOrderIsPreservedWithinKey() :void
    {
        $this->assertSame
        (
            [ 'a' => [ 'third' , 'first' , 'second' ] ] ,
            parseQueryString( 'a=third&a=first&a=second' ) ,
        ) ;
    }

    public function testBracketedKeysAreOpaque() :void
    {
        // PHP's parse_str would interpret `a[]` as array-append; we
        // keep the key literal.
        $this->assertSame
        (
            [ 'a[]' => [ '1' , '2' ] ] ,
            parseQueryString( 'a[]=1&a[]=2' ) ,
        ) ;
    }

    public function testFlagStyleKey() :void
    {
        $this->assertSame
        (
            [ 'flag' => [ '' ] ] ,
            parseQueryString( 'flag' ) ,
        ) ;
    }

    public function testEmptyValue() :void
    {
        $this->assertSame
        (
            [ 'a' => [ '' ] ] ,
            parseQueryString( 'a=' ) ,
        ) ;
    }

    public function testRfcEncodedSpaceIsDecoded() :void
    {
        $this->assertSame
        (
            [ 'q' => [ 'hello world' ] ] ,
            parseQueryString( 'q=hello%20world' ) ,
        ) ;
    }

    public function testFormEncodedPlusIsDecodedAsSpaceWhenFlagged() :void
    {
        $this->assertSame
        (
            [ 'q' => [ 'hello world' ] ] ,
            parseQueryString( 'q=hello+world' , true ) ,
        ) ;
    }

    public function testFormEncodedPlusIsKeptAsPlusByDefault() :void
    {
        // RFC 3986 percent-decoding does NOT touch `+`.
        $this->assertSame
        (
            [ 'q' => [ 'hello+world' ] ] ,
            parseQueryString( 'q=hello+world' ) ,
        ) ;
    }

    public function testEmptySegmentsAreSkipped() :void
    {
        $this->assertSame
        (
            [ 'a' => [ '1' ] , 'b' => [ '2' ] ] ,
            parseQueryString( '&&a=1&&b=2&' ) ,
        ) ;
    }

    public function testPairWithEmptyNameIsSkipped() :void
    {
        // `=foo` decodes to an empty name → the whole pair is dropped.
        $this->assertSame( [] , parseQueryString( '=foo' ) ) ;
        $this->assertSame
        (
            [ 'a' => [ '1' ] ] ,
            parseQueryString( '=foo&a=1' ) ,
        ) ;
    }

    public function testValueWithEqualsSignIsPreservedAfterFirstSeparator() :void
    {
        // base64-padded tokens often contain `=`.
        $this->assertSame
        (
            [ 'token' => [ 'eyJhbGc=.payload=' ] ] ,
            parseQueryString( 'token=eyJhbGc=.payload=' ) ,
        ) ;
    }
}
