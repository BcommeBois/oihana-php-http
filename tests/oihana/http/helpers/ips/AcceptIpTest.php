<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\acceptIp;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\acceptIp()}.
 */
class AcceptIpTest extends TestCase
{
    public function testNullOrEmptyInputReturnsNull(): void
    {
        $this->assertNull( acceptIp( null , true ) ) ;
        $this->assertNull( acceptIp( ''   , true ) ) ;
    }

    public function testInvalidInputReturnsNull(): void
    {
        $this->assertNull( acceptIp( 'not-an-ip' , true ) ) ;
        $this->assertNull( acceptIp( '999.999.0.1' , true ) ) ;
    }

    public function testValidPublicIpReturnedAsCanonical(): void
    {
        $this->assertSame( '8.8.8.8' , acceptIp( '8.8.8.8' , true  ) ) ;
        $this->assertSame( '8.8.8.8' , acceptIp( '8.8.8.8' , false ) ) ;
    }

    public function testPrivateIpAcceptedWhenAllowed(): void
    {
        $this->assertSame( '10.0.0.1'    , acceptIp( '10.0.0.1'    , true ) ) ;
        $this->assertSame( '192.168.1.1' , acceptIp( '192.168.1.1' , true ) ) ;
    }

    public function testPrivateIpRejectedWhenAllowPrivateFalse(): void
    {
        $this->assertNull( acceptIp( '10.0.0.1'    , false ) ) ;
        $this->assertNull( acceptIp( '192.168.1.1' , false ) ) ;
        $this->assertNull( acceptIp( '127.0.0.1'   , false ) ) ;
    }

    public function testIPv4MappedIPv6IsCanonicalised(): void
    {
        $this->assertSame( '192.168.1.10' , acceptIp( '::ffff:192.168.1.10' , true ) ) ;
    }
}
