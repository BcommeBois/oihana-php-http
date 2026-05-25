<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\isPublicIp;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\isPublicIp()}.
 */
class IsPublicIpTest extends TestCase
{
    public function testPublicIPv4Addresses(): void
    {
        $this->assertTrue( isPublicIp( '8.8.8.8'  ) ) ;
        $this->assertTrue( isPublicIp( '1.1.1.1'  ) ) ;
        $this->assertTrue( isPublicIp( '93.184.216.34' ) ) ;
    }

    public function testPrivateIPv4Addresses(): void
    {
        $this->assertFalse( isPublicIp( '10.0.0.1'    ) ) ;
        $this->assertFalse( isPublicIp( '172.16.0.1'  ) ) ;
        $this->assertFalse( isPublicIp( '192.168.1.1' ) ) ;
    }

    public function testReservedIPv4Addresses(): void
    {
        $this->assertFalse( isPublicIp( '127.0.0.1'    ) ) ;
        $this->assertFalse( isPublicIp( '169.254.0.1'  ) ) ; // link-local
        $this->assertFalse( isPublicIp( '0.0.0.0'      ) ) ;
        $this->assertFalse( isPublicIp( '255.255.255.255' ) ) ;
    }

    public function testPublicIPv6Addresses(): void
    {
        $this->assertTrue( isPublicIp( '2001:4860:4860::8888' ) ) ; // Google DNS
        $this->assertTrue( isPublicIp( '2606:4700:4700::1111' ) ) ; // Cloudflare
    }

    public function testReservedIPv6Addresses(): void
    {
        $this->assertFalse( isPublicIp( '::1'            ) ) ; // loopback
        $this->assertFalse( isPublicIp( 'fe80::1'        ) ) ; // link-local
        $this->assertFalse( isPublicIp( 'fc00::1'        ) ) ; // unique local
    }

    public function testInvalidInputs(): void
    {
        $this->assertFalse( isPublicIp( ''            ) ) ;
        $this->assertFalse( isPublicIp( 'not-an-ip'   ) ) ;
        $this->assertFalse( isPublicIp( '999.999.999.999' ) ) ;
    }
}
