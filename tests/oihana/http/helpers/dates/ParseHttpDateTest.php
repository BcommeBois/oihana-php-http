<?php

namespace tests\oihana\http\helpers\dates ;

use DateTimeImmutable ;
use DateTimeZone ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\dates\parseHttpDate ;

/**
 * Unit coverage for {@see \oihana\http\helpers\dates\parseHttpDate()}.
 */
class ParseHttpDateTest extends TestCase
{
    public function testNullReturnsNull() :void
    {
        $this->assertNull( parseHttpDate( null ) ) ;
    }

    public function testEmptyStringReturnsNull() :void
    {
        $this->assertNull( parseHttpDate( '' ) ) ;
    }

    public function testWhitespaceOnlyReturnsNull() :void
    {
        $this->assertNull( parseHttpDate( "   \t \n " ) ) ;
    }

    public function testImfFixdate() :void
    {
        $dt = parseHttpDate( 'Sun, 06 Nov 1994 08:49:37 GMT' ) ;

        $this->assertInstanceOf( DateTimeImmutable::class , $dt ) ;
        $this->assertSame( '1994-11-06T08:49:37+00:00' , $dt->format( DATE_ATOM ) ) ;
        $this->assertSame( 'UTC' , $dt->getTimezone()->getName() ) ;
    }

    public function testRfc850Format() :void
    {
        $dt = parseHttpDate( 'Sunday, 06-Nov-94 08:49:37 GMT' ) ;

        $this->assertInstanceOf( DateTimeImmutable::class , $dt ) ;
        $this->assertSame( '1994-11-06T08:49:37+00:00' , $dt->format( DATE_ATOM ) ) ;
    }

    public function testAsctimeWithSingleDigitDayAndDoubleSpace() :void
    {
        $dt = parseHttpDate( 'Sun Nov  6 08:49:37 1994' ) ;

        $this->assertInstanceOf( DateTimeImmutable::class , $dt ) ;
        $this->assertSame( '1994-11-06T08:49:37+00:00' , $dt->format( DATE_ATOM ) ) ;
    }

    public function testAsctimeWithTwoDigitDay() :void
    {
        $dt = parseHttpDate( 'Thu Dec 31 23:59:59 2026' ) ;

        $this->assertInstanceOf( DateTimeImmutable::class , $dt ) ;
        $this->assertSame( '2026-12-31T23:59:59+00:00' , $dt->format( DATE_ATOM ) ) ;
    }

    public function testSurroundingWhitespaceIsTrimmed() :void
    {
        $dt = parseHttpDate( "   Thu, 31 Dec 2026 23:59:59 GMT  \n" ) ;

        $this->assertInstanceOf( DateTimeImmutable::class , $dt ) ;
        $this->assertSame( '2026-12-31T23:59:59+00:00' , $dt->format( DATE_ATOM ) ) ;
    }

    public function testNonGMTSuffixIsRejected() :void
    {
        // RFC 7231 mandates `GMT`. `UTC` is a common mistake but not legal.
        $this->assertNull( parseHttpDate( 'Thu, 31 Dec 2026 23:59:59 UTC' ) ) ;
    }

    public function testOffsetSuffixIsRejected() :void
    {
        // Even `+0000` (numerically equivalent) is not a valid HTTP-date.
        $this->assertNull( parseHttpDate( 'Thu, 31 Dec 2026 23:59:59 +0000' ) ) ;
    }

    public function testInvalidStringReturnsNull() :void
    {
        $this->assertNull( parseHttpDate( 'tomorrow'        ) ) ;
        $this->assertNull( parseHttpDate( '2026-12-31'      ) ) ;
        $this->assertNull( parseHttpDate( 'garbage'         ) ) ;
        $this->assertNull( parseHttpDate( '1767225600'      ) ) ; // unix ts
    }

    public function testRfc850TwoDigitYearWrapsViaPhpSlidingWindow() :void
    {
        // PHP's `y` format treats 00-69 as 2000-2069, 70-99 as 1970-1999.
        // 06-Nov-2026 is a Friday — picking the right weekday matters
        // because PHP's `createFromFormat` will silently shift the date
        // to the next matching weekday if a wrong name is supplied.
        $dt = parseHttpDate( 'Friday, 06-Nov-26 08:49:37 GMT' ) ;

        $this->assertInstanceOf( DateTimeImmutable::class , $dt ) ;
        $this->assertSame( '2026-11-06T08:49:37+00:00' , $dt->format( DATE_ATOM ) ) ;
    }

    public function testReturnsUtcTimezoneEvenWhenPhpDefaultIsDifferent() :void
    {
        $previousTz = date_default_timezone_get() ;
        date_default_timezone_set( 'Europe/Paris' ) ;

        try
        {
            $dt = parseHttpDate( 'Thu, 31 Dec 2026 23:59:59 GMT' ) ;
            $this->assertNotNull( $dt ) ;
            $this->assertSame( 'UTC' , $dt->getTimezone()->getName() ) ;
        }
        finally
        {
            date_default_timezone_set( $previousTz ) ;
        }
    }
}
