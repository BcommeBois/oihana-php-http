<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\url\isAbsoluteUrl ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\isAbsoluteUrl()}.
 */
class IsAbsoluteUrlTest extends TestCase
{
    public function testHttpsUrl() :void
    {
        $this->assertTrue( isAbsoluteUrl( 'https://example.com/path' ) ) ;
    }

    public function testHttpUrl() :void
    {
        $this->assertTrue( isAbsoluteUrl( 'http://example.com' ) ) ;
    }

    public function testMailto() :void
    {
        $this->assertTrue( isAbsoluteUrl( 'mailto:alice@example.com' ) ) ;
    }

    public function testFile() :void
    {
        $this->assertTrue( isAbsoluteUrl( 'file:///etc/passwd' ) ) ;
    }

    public function testCustomScheme() :void
    {
        $this->assertTrue( isAbsoluteUrl( 'tel:+33123456789'        ) ) ;
        $this->assertTrue( isAbsoluteUrl( 'x-custom-app://payload'  ) ) ;
        $this->assertTrue( isAbsoluteUrl( 'data:text/plain;base64,' ) ) ;
    }

    public function testProtocolRelativeIsFalse() :void
    {
        // // … has authority but no scheme.
        $this->assertFalse( isAbsoluteUrl( '//example.com/path' ) ) ;
    }

    public function testPathAbsoluteIsFalse() :void
    {
        $this->assertFalse( isAbsoluteUrl( '/api/v1' ) ) ;
    }

    public function testRelativeIsFalse() :void
    {
        $this->assertFalse( isAbsoluteUrl( 'api/v1' ) ) ;
        $this->assertFalse( isAbsoluteUrl( '../up'  ) ) ;
        $this->assertFalse( isAbsoluteUrl( './here' ) ) ;
    }

    public function testEmptyIsFalse() :void
    {
        $this->assertFalse( isAbsoluteUrl( '' ) ) ;
    }

    public function testSchemeMustStartWithLetter() :void
    {
        // RFC 3986: scheme = ALPHA *( ALPHA / DIGIT / "+" / "-" / "." )
        $this->assertFalse( isAbsoluteUrl( '1http://example.com' ) ) ;
    }
}
