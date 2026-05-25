<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\canonicalIp;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\canonicalIp()}.
 */
class CanonicalIpTest extends TestCase
{
    public function testIPv4PassesThrough(): void
    {
        $this->assertSame( '127.0.0.1' , canonicalIp( '127.0.0.1' ) ) ;
        $this->assertSame( '8.8.8.8'   , canonicalIp( '8.8.8.8'   ) ) ;
    }

    public function testIPv6IsCompressed(): void
    {
        $this->assertSame
        (
            '2001:db8::ff00:42:8329' ,
            canonicalIp( '2001:0db8:0000:0000:0000:ff00:0042:8329' )
        ) ;
    }

    public function testIPv4MappedIPv6IsConvertedToIPv4(): void
    {
        $this->assertSame( '192.168.1.10' , canonicalIp( '::ffff:192.168.1.10' ) ) ;
    }

    public function testInvalidInputReturnedAsIs(): void
    {
        // Defensive contract: never throw on garbage input.
        $this->assertSame( 'not-an-ip' , canonicalIp( 'not-an-ip' ) ) ;
        $this->assertSame( ''          , canonicalIp( ''          ) ) ;
    }
}
