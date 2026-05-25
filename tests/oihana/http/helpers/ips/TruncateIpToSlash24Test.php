<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\truncateIpToSlash24;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\truncateIpToSlash24()}.
 */
class TruncateIpToSlash24Test extends TestCase
{
    public function testEmptyStringReturnedAsIs() :void
    {
        $this->assertSame( '' , truncateIpToSlash24( '' ) ) ;
    }

    public function testIPv4InvalidShapeReturnedAsIs() :void
    {
        // Strings that look IP-ish but do not match a.b.c.d are not
        // truncated — the helper stays defensive on garbage.
        $this->assertSame( '198.51.100'         , truncateIpToSlash24( '198.51.100'         ) ) ;
        $this->assertSame( '198.51.100.42.13'   , truncateIpToSlash24( '198.51.100.42.13'   ) ) ;
        $this->assertSame( 'not-an-ip'          , truncateIpToSlash24( 'not-an-ip'          ) ) ;
    }

    public function testIPv4LastOctetTruncatedToZero() :void
    {
        $this->assertSame( '198.51.100.0' , truncateIpToSlash24( '198.51.100.42' ) ) ;
        $this->assertSame( '8.8.8.0'      , truncateIpToSlash24( '8.8.8.8'       ) ) ;
        $this->assertSame( '10.0.0.0'     , truncateIpToSlash24( '10.0.0.255'    ) ) ;
    }

    public function testIPv4WithZeroLastOctetIsIdempotent() :void
    {
        $this->assertSame( '198.51.100.0' , truncateIpToSlash24( '198.51.100.0' ) ) ;
    }

    public function testIPv6ReturnedUntouched() :void
    {
        // Non-IPv4 shapes (no four dot-separated parts) pass through
        // untouched — IPv6 is the canonical example.
        $this->assertSame( '2001:db8::1'              , truncateIpToSlash24( '2001:db8::1'              ) ) ;
        $this->assertSame( '::1'                      , truncateIpToSlash24( '::1'                      ) ) ;
        $this->assertSame( '::ffff:192.168.1.10'      , truncateIpToSlash24( '::ffff:192.168.1.10'      ) ) ;
    }

    public function testNullInReturnsNullOut() :void
    {
        $this->assertNull( truncateIpToSlash24( null ) ) ;
    }
}
