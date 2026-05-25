<?php

namespace tests\oihana\http\helpers\cookies ;

use oihana\http\enums\CookieOption ;
use oihana\http\enums\SameSite ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\cookies\buildSetCookieHeader ;

/**
 * Unit coverage for {@see \oihana\http\helpers\cookies\buildSetCookieHeader()}.
 */
class BuildSetCookieHeaderTest extends TestCase
{
    public function testDefaultsProduceMinimalHeader() :void
    {
        $this->assertSame
        (
            'access_token=abc123; Path=/; Max-Age=3600; SameSite=Lax; HttpOnly' ,
            buildSetCookieHeader( 'access_token' , 'abc123' , 3600 ) ,
        ) ;
    }

    public function testNullValueIsRenderedAsEmpty() :void
    {
        $this->assertSame
        (
            'access_token=; Path=/; Max-Age=0; SameSite=Lax; HttpOnly' ,
            buildSetCookieHeader( 'access_token' , null , 0 ) ,
        ) ;
    }

    public function testSecureAttributeIsAppendedWhenEnabled() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc123' ,
            3600 ,
            [ CookieOption::SECURE => true ] ,
        ) ;

        $this->assertSame
        (
            'access_token=abc123; Path=/; Max-Age=3600; SameSite=Lax; HttpOnly; Secure' ,
            $header ,
        ) ;
    }

    public function testDomainAttributeIsAppendedWhenNotEmpty() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc123' ,
            3600 ,
            [ CookieOption::DOMAIN => 'example.com' ] ,
        ) ;

        $this->assertSame
        (
            'access_token=abc123; Path=/; Max-Age=3600; SameSite=Lax; HttpOnly; Domain=example.com' ,
            $header ,
        ) ;
    }

    public function testEmptyDomainIsSkipped() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc123' ,
            3600 ,
            [ CookieOption::DOMAIN => '' ] ,
        ) ;

        $this->assertStringNotContainsString( 'Domain=' , $header ) ;
    }

    public function testHttpOnlyCanBeDisabled() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc123' ,
            3600 ,
            [ CookieOption::HTTP_ONLY => false ] ,
        ) ;

        $this->assertSame
        (
            'access_token=abc123; Path=/; Max-Age=3600; SameSite=Lax' ,
            $header ,
        ) ;
    }

    public function testCustomPathIsHonored() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc123' ,
            3600 ,
            [ CookieOption::PATH => '/api' ] ,
        ) ;

        $this->assertStringContainsString( 'Path=/api' , $header ) ;
    }

    public function testCustomSameSiteIsHonored() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc123' ,
            3600 ,
            [ CookieOption::SAME_SITE => SameSite::STRICT ] ,
        ) ;

        $this->assertStringContainsString( 'SameSite=Strict' , $header ) ;
    }

    public function testAllOptionsCombined() :void
    {
        $header = buildSetCookieHeader
        (
            'refresh_token' ,
            'rt_xyz' ,
            2592000 ,
            [
                CookieOption::DOMAIN    => 'api.example.com' ,
                CookieOption::SECURE    => true              ,
                CookieOption::PATH      => '/auth'           ,
                CookieOption::SAME_SITE => SameSite::NONE    ,
                CookieOption::HTTP_ONLY => true              ,
            ] ,
        ) ;

        $this->assertSame
        (
            'refresh_token=rt_xyz; Path=/auth; Max-Age=2592000; SameSite=None; HttpOnly; Secure; Domain=api.example.com' ,
            $header ,
        ) ;
    }

    public function testEmptyOptionsArrayMatchesDefaults() :void
    {
        $this->assertSame
        (
            buildSetCookieHeader( 'x' , 'y' , 60 ) ,
            buildSetCookieHeader( 'x' , 'y' , 60 , [] ) ,
        ) ;
    }

    public function testAttributesAreJoinedWithSemicolonSpace() :void
    {
        // RFC 6265 only mandates `;` as the attribute separator, but
        // every major browser and proxy expects the canonical `'; '`
        // form. Lock the format down so a future refactor cannot
        // silently drop the space.
        $header = buildSetCookieHeader( 'a' , 'b' , 1 ) ;

        $this->assertStringContainsString( '; ' , $header ) ;
        $this->assertStringNotContainsString( ';;' , $header ) ;
    }
}
