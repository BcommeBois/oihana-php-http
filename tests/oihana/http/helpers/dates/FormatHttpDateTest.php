<?php

namespace tests\oihana\http\helpers\dates ;

use DateTime ;
use DateTimeImmutable ;
use DateTimeZone ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\dates\formatHttpDate ;
use function oihana\http\helpers\dates\parseHttpDate ;

/**
 * Unit coverage for {@see \oihana\http\helpers\dates\formatHttpDate()}.
 */
class FormatHttpDateTest extends TestCase
{
    public function testUtcImmutableFormatsAsImfFixdate() :void
    {
        $dt = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;

        $this->assertSame
        (
            'Thu, 31 Dec 2026 23:59:59 GMT' ,
            formatHttpDate( $dt ) ,
        ) ;
    }

    public function testNonUtcInputIsConvertedBeforeFormatting() :void
    {
        // 00:00:00 in CEST (UTC+2) is 22:00:00 the previous day in UTC.
        $cest = new DateTimeImmutable( '2026-07-01 00:00:00' , new DateTimeZone( 'Europe/Paris' ) ) ;

        $this->assertSame
        (
            'Tue, 30 Jun 2026 22:00:00 GMT' ,
            formatHttpDate( $cest ) ,
        ) ;
    }

    public function testMutableDateTimeInterfaceIsAlsoAccepted() :void
    {
        $mutable = new DateTime( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;

        $this->assertSame
        (
            'Thu, 31 Dec 2026 23:59:59 GMT' ,
            formatHttpDate( $mutable ) ,
        ) ;
    }

    public function testInputIsNotMutatedWhenAlreadyUTC() :void
    {
        $dt = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;

        formatHttpDate( $dt ) ;

        $this->assertSame( 'UTC' , $dt->getTimezone()->getName() ) ;
    }

    public function testMutableInputIsNotMutated() :void
    {
        $tz = new DateTimeZone( 'America/Los_Angeles' ) ;
        $mutable = new DateTime( '2026-12-31 15:00:00' , $tz ) ;

        formatHttpDate( $mutable ) ;

        // The mutable instance must still be in its original timezone.
        $this->assertSame( 'America/Los_Angeles' , $mutable->getTimezone()->getName() ) ;
    }

    public function testOutputAlwaysEndsWithGMT() :void
    {
        $samples =
        [
            new DateTimeImmutable( '2026-01-01 00:00:00' , new DateTimeZone( 'UTC'             ) ) ,
            new DateTimeImmutable( '2026-07-01 12:34:56' , new DateTimeZone( 'Asia/Tokyo'      ) ) ,
            new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'Pacific/Auckland') ) ,
        ] ;

        foreach ( $samples as $sample )
        {
            $this->assertStringEndsWith( ' GMT' , formatHttpDate( $sample ) ) ;
        }
    }

    public function testRoundtripsWithParseHttpDate() :void
    {
        $original = new DateTimeImmutable( '2026-12-31 23:59:59' , new DateTimeZone( 'UTC' ) ) ;

        $header   = formatHttpDate( $original ) ;
        $reparsed = parseHttpDate( $header ) ;

        $this->assertNotNull( $reparsed ) ;
        $this->assertSame
        (
            $original->getTimestamp() ,
            $reparsed->getTimestamp() ,
        ) ;
    }
}
