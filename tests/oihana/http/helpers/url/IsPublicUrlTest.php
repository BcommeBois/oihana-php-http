<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\url\isPublicUrl ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\isPublicUrl()}.
 */
class IsPublicUrlTest extends TestCase
{
    public function testPublicFqdn() :void
    {
        $this->assertTrue( isPublicUrl( 'https://api.example.com'      ) ) ;
        $this->assertTrue( isPublicUrl( 'http://example.com'          ) ) ;
        $this->assertTrue( isPublicUrl( 'https://sub.domain.example.org/path?x=1' ) ) ;
    }

    public function testPublicFqdnWithPort() :void
    {
        $this->assertTrue( isPublicUrl( 'https://api.example.com:8443/v1' ) ) ;
    }

    public function testPublicIPv4() :void
    {
        $this->assertTrue( isPublicUrl( 'http://8.8.8.8'         ) ) ;
        $this->assertTrue( isPublicUrl( 'https://1.1.1.1/health' ) ) ;
    }

    public function testPublicIPv6() :void
    {
        $this->assertTrue( isPublicUrl( 'http://[2001:4860:4860::8888]'        ) ) ;
        $this->assertTrue( isPublicUrl( 'https://[2606:4700:4700::1111]:443/x' ) ) ;
    }

    public function testLocalhost() :void
    {
        $this->assertFalse( isPublicUrl( 'http://localhost'        ) ) ;
        $this->assertFalse( isPublicUrl( 'http://localhost:8080/x' ) ) ;
        $this->assertFalse( isPublicUrl( 'https://LOCALHOST'       ) ) ; // case-insensitive
    }

    public function testLocalhostSubdomain() :void
    {
        $this->assertFalse( isPublicUrl( 'http://app.localhost'        ) ) ;
        $this->assertFalse( isPublicUrl( 'http://api.app.localhost:80' ) ) ;
    }

    public function testLoopbackIPv4() :void
    {
        $this->assertFalse( isPublicUrl( 'http://127.0.0.1'      ) ) ;
        $this->assertFalse( isPublicUrl( 'http://127.0.0.1:8080' ) ) ;
    }

    public function testPrivateIPv4() :void
    {
        $this->assertFalse( isPublicUrl( 'http://10.0.0.1'    ) ) ;
        $this->assertFalse( isPublicUrl( 'http://172.16.0.1'  ) ) ;
        $this->assertFalse( isPublicUrl( 'http://192.168.1.1' ) ) ;
    }

    public function testLoopbackIPv6() :void
    {
        $this->assertFalse( isPublicUrl( 'http://[::1]'      ) ) ;
        $this->assertFalse( isPublicUrl( 'http://[::1]:9000' ) ) ;
    }

    /**
     * Corrected behaviour vs. a naive "anything-but-::1 is public": private
     * (RFC 4193 unique-local) and link-local IPv6 literals are rejected too.
     */
    public function testPrivateIPv6() :void
    {
        $this->assertFalse( isPublicUrl( 'http://[fd00::1]' ) ) ; // unique local
        $this->assertFalse( isPublicUrl( 'http://[fe80::1]' ) ) ; // link-local
    }

    public function testNoHost() :void
    {
        $this->assertFalse( isPublicUrl( '/relative/path' ) ) ;
        $this->assertFalse( isPublicUrl( 'api/v1'         ) ) ;
        $this->assertFalse( isPublicUrl( ''               ) ) ;
    }
}
