<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\truncateIpToSlash48;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\truncateIpToSlash48()}.
 */
class TruncateIpToSlash48Test extends TestCase
{
    public function testEmptyStringReturnedAsIs() :void
    {
        $this->assertSame( '' , truncateIpToSlash48( '' ) ) ;
    }

    public function testNullInReturnsNullOut() :void
    {
        $this->assertNull( truncateIpToSlash48( null ) ) ;
    }

    public function testFullAddressTruncatedTo48BitPrefix() :void
    {
        $this->assertSame
        (
            '2001:db8:cafe::' ,
            truncateIpToSlash48( '2001:db8:cafe:1234:5678:9abc:def0:1111' ) ,
        ) ;
    }

    public function testCompressedAddressTruncated() :void
    {
        $this->assertSame
        (
            '2001:db8:cafe::' ,
            truncateIpToSlash48( '2001:db8:cafe::1' ) ,
        ) ;
    }

    public function testAlreadyTruncatedAddressIsIdempotent() :void
    {
        $this->assertSame
        (
            '2001:db8:cafe::' ,
            truncateIpToSlash48( '2001:db8:cafe::' ) ,
        ) ;
    }

    public function testLoopbackTruncatedToZero() :void
    {
        // ::1 has all-zero high bits, so the /48 prefix is also all-zero.
        $this->assertSame( '::' , truncateIpToSlash48( '::1' ) ) ;
    }

    public function testCanonicalisesNonCompressedInput() :void
    {
        // inet_pton + inet_ntop normalises 2001:0db8 → 2001:db8.
        $this->assertSame
        (
            '2001:db8:cafe::' ,
            truncateIpToSlash48( '2001:0db8:cafe:0:0:0:0:0' ) ,
        ) ;
    }

    public function testIPv4InputReturnedAsIs() :void
    {
        // IPv4 is handled by truncateIpToSlash24; this helper stays a
        // no-op on non-IPv6 input.
        $this->assertSame( '198.51.100.42' , truncateIpToSlash48( '198.51.100.42' ) ) ;
        $this->assertSame( '8.8.8.8'       , truncateIpToSlash48( '8.8.8.8'       ) ) ;
    }

    public function testIPv4MappedIPv6ReturnedAsIs() :void
    {
        // `::ffff:1.2.3.4` is a valid IPv6 syntax, but PHP's
        // FILTER_FLAG_IPV6 still accepts it. Verify the helper produces
        // a sensible /48 form — the IPv4-mapped prefix is all-zero, so
        // the /48 zero-fills.
        $this->assertSame( '::' , truncateIpToSlash48( '::ffff:192.168.1.10' ) ) ;
    }

    public function testMalformedInputReturnedAsIs() :void
    {
        $this->assertSame( 'not-an-ip'    , truncateIpToSlash48( 'not-an-ip'    ) ) ;
        $this->assertSame( '2001:db8::xx' , truncateIpToSlash48( '2001:db8::xx' ) ) ;
        $this->assertSame( '::g'          , truncateIpToSlash48( '::g'          ) ) ;
    }
}
