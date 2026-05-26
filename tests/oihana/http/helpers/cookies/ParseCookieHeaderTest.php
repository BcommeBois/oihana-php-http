<?php

namespace tests\oihana\http\helpers\cookies ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\cookies\parseCookieHeader ;

/**
 * Unit coverage for {@see \oihana\http\helpers\cookies\parseCookieHeader()}.
 */
class ParseCookieHeaderTest extends TestCase
{
    public function testEmptyHeaderReturnsEmptyArray() :void
    {
        $this->assertSame( [] , parseCookieHeader( '' ) ) ;
    }

    public function testWhitespaceOnlyHeaderReturnsEmptyArray() :void
    {
        $this->assertSame( [] , parseCookieHeader( '   ' ) ) ;
    }

    public function testSinglePair() :void
    {
        $this->assertSame
        (
            [ 'PHPSESSID' => 'abc' ] ,
            parseCookieHeader( 'PHPSESSID=abc' ) ,
        ) ;
    }

    public function testMultiplePairs() :void
    {
        $this->assertSame
        (
            [ 'PHPSESSID' => 'abc' , 'user' => 'jane' ] ,
            parseCookieHeader( 'PHPSESSID=abc; user=jane' ) ,
        ) ;
    }

    public function testToleratesMissingSpaceAfterSemicolon() :void
    {
        $this->assertSame
        (
            [ 'a' => '1' , 'b' => '2' ] ,
            parseCookieHeader( 'a=1;b=2' ) ,
        ) ;
    }

    public function testValuesWithEqualsArePreservedAfterFirstSeparator() :void
    {
        $this->assertSame
        (
            [ 'token' => 'eyJhbGc=.eyJzdWI=' ] ,
            parseCookieHeader( 'token=eyJhbGc=.eyJzdWI=' ) ,
        ) ;
    }

    public function testFlagStyleEntryHasEmptyValue() :void
    {
        $this->assertSame
        (
            [ 'flag' => '' , 'name' => 'jane' ] ,
            parseCookieHeader( 'flag; name=jane' ) ,
        ) ;
    }

    public function testEmptyNameSilentlyDropped() :void
    {
        $this->assertSame
        (
            [ 'a' => '1' ] ,
            parseCookieHeader( '=orphan; a=1' ) ,
        ) ;
    }

    public function testDuplicateNameLastOccurrenceWins() :void
    {
        $this->assertSame
        (
            [ 'a' => '2' ] ,
            parseCookieHeader( 'a=1; a=2' ) ,
        ) ;
    }

    public function testValuesAreNotURLDecoded() :void
    {
        // Caller is responsible for URL-decoding when needed.
        $this->assertSame
        (
            [ 'q' => 'hello%20world' ] ,
            parseCookieHeader( 'q=hello%20world' ) ,
        ) ;
    }

    public function testEmptySegmentsAreSkipped() :void
    {
        $this->assertSame
        (
            [ 'a' => '1' , 'b' => '2' ] ,
            parseCookieHeader( ';; a=1; ; b=2 ;' ) ,
        ) ;
    }

    public function testNamesAreTrimmed() :void
    {
        $this->assertSame
        (
            [ 'a' => '1' , 'b' => '2' ] ,
            parseCookieHeader( '  a  =1;   b  =2' ) ,
        ) ;
    }
}
