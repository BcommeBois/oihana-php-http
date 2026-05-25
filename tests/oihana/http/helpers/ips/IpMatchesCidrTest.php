<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\ipMatchesCidr;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\ipMatchesCidr()}.
 *
 * The helper drives every trusted-proxy decision in
 * {@see \oihana\http\helpers\ips\getClientIp()}. It must fail closed on
 * malformed inputs and reject mismatched address families.
 */
class IpMatchesCidrTest extends TestCase
{
    public function testIPv4InsideCidr(): void
    {
        $this->assertTrue ( ipMatchesCidr( '10.1.2.3'    , '10.0.0.0/8'  ) ) ;
        $this->assertTrue ( ipMatchesCidr( '192.168.1.5' , '192.168.0.0/16' ) ) ;
        $this->assertFalse( ipMatchesCidr( '11.0.0.1'    , '10.0.0.0/8'  ) ) ;
        $this->assertFalse( ipMatchesCidr( '192.169.0.1' , '192.168.0.0/16' ) ) ;
    }

    public function testIPv4BareIpIsExactMatch(): void
    {
        $this->assertTrue ( ipMatchesCidr( '127.0.0.1' , '127.0.0.1' ) ) ;
        $this->assertFalse( ipMatchesCidr( '127.0.0.2' , '127.0.0.1' ) ) ;
    }

    public function testIPv4Slash32EqualsBareMatch(): void
    {
        $this->assertTrue ( ipMatchesCidr( '8.8.8.8' , '8.8.8.8/32' ) ) ;
        $this->assertFalse( ipMatchesCidr( '8.8.8.9' , '8.8.8.8/32' ) ) ;
    }

    public function testIPv4Slash0MatchesEverything(): void
    {
        $this->assertTrue( ipMatchesCidr( '8.8.8.8'    , '0.0.0.0/0' ) ) ;
        $this->assertTrue( ipMatchesCidr( '192.168.1.1' , '0.0.0.0/0' ) ) ;
    }

    public function testIPv6InsideCidr(): void
    {
        $this->assertTrue ( ipMatchesCidr( '2001:db8::1'         , '2001:db8::/32' ) ) ;
        $this->assertTrue ( ipMatchesCidr( '2001:db8:cafe::beef' , '2001:db8::/32' ) ) ;
        $this->assertFalse( ipMatchesCidr( '2001:db9::1'         , '2001:db8::/32' ) ) ;
    }

    public function testIPv6BareIpIsExactMatch(): void
    {
        $this->assertTrue ( ipMatchesCidr( '::1' , '::1'  ) ) ;
        $this->assertFalse( ipMatchesCidr( '::2' , '::1'  ) ) ;
    }

    public function testMismatchedFamiliesAlwaysFalse(): void
    {
        $this->assertFalse( ipMatchesCidr( '127.0.0.1' , '::1/128' ) ) ;
        $this->assertFalse( ipMatchesCidr( '::1'       , '127.0.0.0/8' ) ) ;
    }

    public function testInvalidIpReturnsFalse(): void
    {
        $this->assertFalse( ipMatchesCidr( 'not-an-ip' , '10.0.0.0/8' ) ) ;
        $this->assertFalse( ipMatchesCidr( ''          , '10.0.0.0/8' ) ) ;
    }

    public function testInvalidRangeReturnsFalse(): void
    {
        $this->assertFalse( ipMatchesCidr( '10.0.0.1' , 'not-a-range'   ) ) ;
        $this->assertFalse( ipMatchesCidr( '10.0.0.1' , '10.0.0.0/abc'  ) ) ;
        $this->assertFalse( ipMatchesCidr( '10.0.0.1' , '10.0.0.0/-1'   ) ) ;
        $this->assertFalse( ipMatchesCidr( '10.0.0.1' , '10.0.0.0/33'   ) ) ;
        $this->assertFalse( ipMatchesCidr( '::1'      , '::/129'        ) ) ;
    }

    public function testCidrWithNonByteAlignedPrefix(): void
    {
        // /20 cuts inside the third byte
        $this->assertTrue ( ipMatchesCidr( '10.1.0.1'  , '10.1.0.0/20' ) ) ;
        $this->assertTrue ( ipMatchesCidr( '10.1.15.1' , '10.1.0.0/20' ) ) ;
        $this->assertFalse( ipMatchesCidr( '10.1.16.1' , '10.1.0.0/20' ) ) ;
    }
}
