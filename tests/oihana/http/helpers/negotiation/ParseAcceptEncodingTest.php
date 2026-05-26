<?php

namespace tests\oihana\http\helpers\negotiation ;

use oihana\http\enums\AcceptField ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\negotiation\parseAcceptEncoding ;

/**
 * Unit coverage for {@see \oihana\http\helpers\negotiation\parseAcceptEncoding()}.
 */
class ParseAcceptEncodingTest extends TestCase
{
    public function testEmptyReturnsEmpty() :void
    {
        $this->assertSame( [] , parseAcceptEncoding( '' ) ) ;
    }

    public function testTypicalBrowserHeader() :void
    {
        $parsed = parseAcceptEncoding( 'gzip, deflate, br;q=1.0' ) ;

        $this->assertSame( 'gzip'    , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'deflate' , $parsed[ 1 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'br'      , $parsed[ 2 ][ AcceptField::TYPE ] ) ;
    }

    public function testEncodingNamesAreLowercased() :void
    {
        $parsed = parseAcceptEncoding( 'GZIP' ) ;
        $this->assertSame( 'gzip' , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
    }

    public function testIdentityEncoding() :void
    {
        $parsed = parseAcceptEncoding( 'identity;q=1, *;q=0' ) ;

        $this->assertSame( 'identity' , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( '*'        , $parsed[ 1 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 0.0        , $parsed[ 1 ][ AcceptField::QUALITY ] ) ;
    }
}
