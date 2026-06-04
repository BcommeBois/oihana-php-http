<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\url\getHost ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\getHost()}.
 */
class GetHostTest extends TestCase
{
    public function testFqdnIsLowercased() :void
    {
        $this->assertSame( 'api.example.com' , getHost( 'https://API.Example.com/path?x=1' ) ) ;
    }

    public function testHostWithPort() :void
    {
        $this->assertSame( 'localhost' , getHost( 'http://localhost:8080' ) ) ;
    }

    public function testIPv4() :void
    {
        $this->assertSame( '127.0.0.1' , getHost( 'http://127.0.0.1' ) ) ;
    }

    public function testIPv6BracketsStripped() :void
    {
        $this->assertSame( '2001:db8::1' , getHost( 'http://[2001:db8::1]:443/x' ) ) ;
        $this->assertSame( '::1'         , getHost( 'http://[::1]' ) ) ;
    }

    public function testWithCredentials() :void
    {
        $this->assertSame( 'example.com' , getHost( 'https://user:pass@example.com/x' ) ) ;
    }

    public function testNoAuthorityReturnsNull() :void
    {
        $this->assertNull( getHost( 'mailto:alice@example.com' ) ) ;
    }

    public function testRelativeReturnsNull() :void
    {
        $this->assertNull( getHost( '/relative/path' ) ) ;
        $this->assertNull( getHost( 'api/v1'         ) ) ;
    }

    public function testEmptyReturnsNull() :void
    {
        $this->assertNull( getHost( '' ) ) ;
    }
}
