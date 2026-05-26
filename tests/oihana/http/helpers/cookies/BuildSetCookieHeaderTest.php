<?php

namespace tests\oihana\http\helpers\cookies ;

use DateTimeImmutable ;
use DateTimeZone ;
use InvalidArgumentException ;
use oihana\http\enums\CookieOption ;
use oihana\http\enums\CookiePriority ;
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

    public function testInvalidNameRejected() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        buildSetCookieHeader( 'foo bar' , 'value' , 60 ) ;
    }

    public function testEmptyNameRejected() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        buildSetCookieHeader( '' , 'value' , 60 ) ;
    }

    public function testValueWithSemicolonRejected() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        buildSetCookieHeader( 'access_token' , 'foo; HttpOnly' , 60 ) ;
    }

    public function testValueWithCRLFRejected() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        buildSetCookieHeader( 'access_token' , "foo\r\nSet-Cookie: evil=1" , 60 ) ;
    }

    public function testValueWithNullByteRejected() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        buildSetCookieHeader( 'access_token' , "foo\x00bar" , 60 ) ;
    }

    public function testExpiresFromDateTimeImmutableIsFormattedAsIMFFixdateGMT() :void
    {
        $dt = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;

        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::EXPIRES => $dt ] ,
        ) ;

        $this->assertStringContainsString( 'Expires=Thu, 31 Dec 2026 23:59:59 GMT' , $header ) ;
    }

    public function testExpiresFromDateTimeIsConvertedToUTC() :void
    {
        // 00:00:00 in CEST (UTC+2) → 22:00:00 the previous day in UTC.
        $dt = new DateTimeImmutable( '2026-07-01 00:00:00' , new DateTimeZone( 'Europe/Paris' ) ) ;

        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::EXPIRES => $dt ] ,
        ) ;

        $this->assertStringContainsString( 'Expires=Tue, 30 Jun 2026 22:00:00 GMT' , $header ) ;
    }

    public function testExpiresFromUnixTimestamp() :void
    {
        // 2026-01-01 00:00:00 UTC = 1767225600
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::EXPIRES => 1767225600 ] ,
        ) ;

        $this->assertStringContainsString( 'Expires=Thu, 01 Jan 2026 00:00:00 GMT' , $header ) ;
    }

    public function testExpiresFromPreFormattedStringIsPassedThrough() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::EXPIRES => 'Wed, 21 Oct 2026 07:28:00 GMT' ] ,
        ) ;

        $this->assertStringContainsString( 'Expires=Wed, 21 Oct 2026 07:28:00 GMT' , $header ) ;
    }

    public function testExpiresNullIsSkipped() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::EXPIRES => null ] ,
        ) ;

        $this->assertStringNotContainsString( 'Expires=' , $header ) ;
    }

    public function testPriorityAttributeIsAppendedWhenSet() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::PRIORITY => CookiePriority::HIGH ] ,
        ) ;

        $this->assertStringContainsString( 'Priority=High' , $header ) ;
    }

    public function testPriorityNullIsSkipped() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::PRIORITY => null ] ,
        ) ;

        $this->assertStringNotContainsString( 'Priority=' , $header ) ;
    }

    public function testInvalidPriorityRejected() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::PRIORITY => 'Critical' ] ,
        ) ;
    }

    public function testPartitionedFlagAppendedWhenTrue() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [
                CookieOption::PARTITIONED => true ,
                CookieOption::SECURE      => true ,
            ] ,
        ) ;

        $this->assertStringEndsWith( '; Partitioned' , $header ) ;
    }

    public function testPartitionedFalseIsSkipped() :void
    {
        $header = buildSetCookieHeader
        (
            'access_token' ,
            'abc' ,
            3600 ,
            [ CookieOption::PARTITIONED => false ] ,
        ) ;

        $this->assertStringNotContainsString( 'Partitioned' , $header ) ;
    }

    public function testAllNewAttributesCombined() :void
    {
        $dt = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;

        $header = buildSetCookieHeader
        (
            'refresh_token' ,
            'rt_xyz' ,
            2592000 ,
            [
                CookieOption::SECURE      => true                  ,
                CookieOption::EXPIRES     => $dt                   ,
                CookieOption::PRIORITY    => CookiePriority::HIGH  ,
                CookieOption::PARTITIONED => true                  ,
            ] ,
        ) ;

        $this->assertSame
        (
            'refresh_token=rt_xyz; Path=/; Max-Age=2592000; SameSite=Lax; HttpOnly; Secure; Expires=Thu, 31 Dec 2026 23:59:59 GMT; Priority=High; Partitioned' ,
            $header ,
        ) ;
    }
}
