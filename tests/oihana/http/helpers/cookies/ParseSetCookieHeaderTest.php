<?php

namespace tests\oihana\http\helpers\cookies ;

use oihana\http\enums\CookieAttribute ;
use oihana\http\enums\SetCookieField ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\cookies\parseSetCookieHeader ;

/**
 * Unit coverage for {@see \oihana\http\helpers\cookies\parseSetCookieHeader()}.
 */
class ParseSetCookieHeaderTest extends TestCase
{
    public function testTypicalHeader() :void
    {
        $this->assertSame
        (
            [
                SetCookieField::NAME       => 'access_token' ,
                SetCookieField::VALUE      => 'abc' ,
                SetCookieField::ATTRIBUTES =>
                [
                    CookieAttribute::PATH      => '/' ,
                    CookieAttribute::MAX_AGE   => '3600' ,
                    CookieAttribute::SAME_SITE => 'Lax' ,
                    CookieAttribute::HTTP_ONLY => true ,
                ] ,
            ] ,
            parseSetCookieHeader( 'access_token=abc; Path=/; Max-Age=3600; SameSite=Lax; HttpOnly' ) ,
        ) ;
    }

    public function testFlagAttributesMapToTrue() :void
    {
        $parsed = parseSetCookieHeader( 'name=val; HttpOnly; Secure; Partitioned' ) ;

        $this->assertTrue( $parsed[ SetCookieField::ATTRIBUTES ][ CookieAttribute::HTTP_ONLY   ] ) ;
        $this->assertTrue( $parsed[ SetCookieField::ATTRIBUTES ][ CookieAttribute::SECURE      ] ) ;
        $this->assertTrue( $parsed[ SetCookieField::ATTRIBUTES ][ CookieAttribute::PARTITIONED ] ) ;
    }

    public function testAttributeNamesAreNormalisedToCanonicalCasing() :void
    {
        $parsed = parseSetCookieHeader( 'name=val; PATH=/; max-age=60; samesite=Strict; httponly' ) ;

        $this->assertArrayHasKey( CookieAttribute::PATH      , $parsed[ SetCookieField::ATTRIBUTES ] ) ;
        $this->assertArrayHasKey( CookieAttribute::MAX_AGE   , $parsed[ SetCookieField::ATTRIBUTES ] ) ;
        $this->assertArrayHasKey( CookieAttribute::SAME_SITE , $parsed[ SetCookieField::ATTRIBUTES ] ) ;
        $this->assertArrayHasKey( CookieAttribute::HTTP_ONLY , $parsed[ SetCookieField::ATTRIBUTES ] ) ;
    }

    public function testEmptyValueIsParsedAsEmptyString() :void
    {
        $parsed = parseSetCookieHeader( 'access_token=; Max-Age=0' ) ;

        $this->assertSame( 'access_token' , $parsed[ SetCookieField::NAME  ] ) ;
        $this->assertSame( ''             , $parsed[ SetCookieField::VALUE ] ) ;
    }

    public function testValueContainingEqualsPreserved() :void
    {
        $parsed = parseSetCookieHeader( 'token=eyJhbGc=.payload; Path=/' ) ;

        $this->assertSame( 'eyJhbGc=.payload' , $parsed[ SetCookieField::VALUE ] ) ;
    }

    public function testUnknownAttributePreservesInputCasing() :void
    {
        $parsed = parseSetCookieHeader( 'name=val; X-Custom=foo' ) ;

        $this->assertArrayHasKey( 'X-Custom' , $parsed[ SetCookieField::ATTRIBUTES ] ) ;
        $this->assertSame( 'foo' , $parsed[ SetCookieField::ATTRIBUTES ][ 'X-Custom' ] ) ;
    }

    public function testFirstSegmentWithoutEqualsIsNameWithEmptyValue() :void
    {
        $parsed = parseSetCookieHeader( 'flagonly' ) ;

        $this->assertSame( 'flagonly' , $parsed[ SetCookieField::NAME  ] ) ;
        $this->assertSame( ''         , $parsed[ SetCookieField::VALUE ] ) ;
    }

    public function testEmptyAttributeSegmentIsSkipped() :void
    {
        // The double `;` produces an empty segment between the pair and Path.
        $parsed = parseSetCookieHeader( 'a=b;;Path=/' ) ;

        $this->assertSame
        (
            [ CookieAttribute::PATH => '/' ] ,
            $parsed[ SetCookieField::ATTRIBUTES ] ,
        ) ;
    }

    public function testAttributeWithEmptyNameIsSkipped() :void
    {
        // The `=x` segment has no name before the `=` → dropped.
        $parsed = parseSetCookieHeader( 'a=b; =x; Path=/' ) ;

        $this->assertSame
        (
            [ CookieAttribute::PATH => '/' ] ,
            $parsed[ SetCookieField::ATTRIBUTES ] ,
        ) ;
    }

    public function testRoundTripWithBuildSetCookieHeader() :void
    {
        $built = 'refresh_token=rt_xyz; Path=/auth; Max-Age=2592000; SameSite=None; HttpOnly; Secure; Domain=api.example.com' ;

        $this->assertSame
        (
            [
                SetCookieField::NAME       => 'refresh_token' ,
                SetCookieField::VALUE      => 'rt_xyz' ,
                SetCookieField::ATTRIBUTES =>
                [
                    CookieAttribute::PATH      => '/auth' ,
                    CookieAttribute::MAX_AGE   => '2592000' ,
                    CookieAttribute::SAME_SITE => 'None' ,
                    CookieAttribute::HTTP_ONLY => true ,
                    CookieAttribute::SECURE    => true ,
                    CookieAttribute::DOMAIN    => 'api.example.com' ,
                ] ,
            ] ,
            parseSetCookieHeader( $built ) ,
        ) ;
    }

    public function testExpiresAttributePreservesHTTPDateValue() :void
    {
        $parsed = parseSetCookieHeader( 'a=1; Expires=Thu, 31 Dec 2026 23:59:59 GMT' ) ;

        // The HTTP-date itself contains commas, but parseSetCookieHeader
        // splits on `;` so the date value stays intact in the attribute.
        $this->assertSame
        (
            'Thu, 31 Dec 2026 23:59:59 GMT' ,
            $parsed[ SetCookieField::ATTRIBUTES ][ CookieAttribute::EXPIRES ] ,
        ) ;
    }
}
