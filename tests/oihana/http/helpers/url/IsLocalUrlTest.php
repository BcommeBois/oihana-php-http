<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\url\isLocalUrl ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\isLocalUrl()}.
 */
class IsLocalUrlTest extends TestCase
{
    public function testLocalhost() :void
    {
        $this->assertTrue( isLocalUrl( 'http://localhost'        ) ) ;
        $this->assertTrue( isLocalUrl( 'http://localhost:8080/x' ) ) ;
        $this->assertTrue( isLocalUrl( 'https://LOCALHOST'       ) ) ;
    }

    public function testLocalhostSubdomain() :void
    {
        $this->assertTrue( isLocalUrl( 'http://app.localhost'        ) ) ;
        $this->assertTrue( isLocalUrl( 'http://api.app.localhost:80' ) ) ;
    }

    public function testPrivateAndLoopbackIps() :void
    {
        $this->assertTrue( isLocalUrl( 'http://127.0.0.1'  ) ) ;
        $this->assertTrue( isLocalUrl( 'http://10.0.0.1'   ) ) ;
        $this->assertTrue( isLocalUrl( 'http://192.168.1.1' ) ) ;
        $this->assertTrue( isLocalUrl( 'http://[::1]'      ) ) ;
        $this->assertTrue( isLocalUrl( 'http://[fd00::1]'  ) ) ; // unique local
    }

    public function testPublicHostsAreNotLocal() :void
    {
        $this->assertFalse( isLocalUrl( 'https://api.example.com'       ) ) ;
        $this->assertFalse( isLocalUrl( 'https://8.8.8.8'               ) ) ;
        $this->assertFalse( isLocalUrl( 'http://[2001:4860:4860::8888]' ) ) ;
    }

    /**
     * Not a strict negation of isPublicUrl(): host-less input is neither
     * public nor local.
     */
    public function testHostLessIsNotLocal() :void
    {
        $this->assertFalse( isLocalUrl( '/relative/path' ) ) ;
        $this->assertFalse( isLocalUrl( 'api/v1'         ) ) ;
        $this->assertFalse( isLocalUrl( ''               ) ) ;
    }
}
