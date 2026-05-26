<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\anonymizeIp;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\anonymizeIp()}.
 */
class AnonymizeIpTest extends TestCase
{
    public function testEmptyStringReturnedAsIs() :void
    {
        $this->assertSame( '' , anonymizeIp( '' ) ) ;
    }

    public function testNullInReturnsNullOut() :void
    {
        $this->assertNull( anonymizeIp( null ) ) ;
    }

    public function testIPv4RoutedToSlash24() :void
    {
        $this->assertSame( '198.51.100.0' , anonymizeIp( '198.51.100.42' ) ) ;
        $this->assertSame( '8.8.8.0'      , anonymizeIp( '8.8.8.8'       ) ) ;
        $this->assertSame( '10.0.0.0'     , anonymizeIp( '10.0.0.255'    ) ) ;
    }

    public function testIPv6RoutedToSlash48() :void
    {
        $this->assertSame
        (
            '2001:db8:cafe::' ,
            anonymizeIp( '2001:db8:cafe:1234:5678:9abc:def0:1111' ) ,
        ) ;
    }

    public function testLoopbackIPv6TruncatedToZero() :void
    {
        $this->assertSame( '::' , anonymizeIp( '::1' ) ) ;
    }

    public function testLoopbackIPv4TruncatedToSlash24() :void
    {
        $this->assertSame( '127.0.0.0' , anonymizeIp( '127.0.0.1' ) ) ;
    }

    public function testMalformedInputReturnedAsIs() :void
    {
        $this->assertSame( 'not-an-ip'    , anonymizeIp( 'not-an-ip'    ) ) ;
        $this->assertSame( '198.51.100'   , anonymizeIp( '198.51.100'   ) ) ; // missing octet
        $this->assertSame( '2001:db8::xx' , anonymizeIp( '2001:db8::xx' ) ) ;
    }

    public function testIPv4MappedIPv6IsTreatedAsIPv6() :void
    {
        // The contract says the helper does not unmap before truncation.
        // The /48 prefix of `::ffff:1.2.3.4` is all-zero.
        $this->assertSame( '::' , anonymizeIp( '::ffff:192.168.1.10' ) ) ;
    }

    public function testAlreadyAnonymisedAddressesAreIdempotent() :void
    {
        $this->assertSame( '198.51.100.0' , anonymizeIp( '198.51.100.0' ) ) ;
        $this->assertSame( '2001:db8:cafe::' , anonymizeIp( '2001:db8:cafe::' ) ) ;
    }
}
