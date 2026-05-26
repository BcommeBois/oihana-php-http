<?php

namespace tests\oihana\http\helpers\cookies ;

use InvalidArgumentException ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\cookies\validateCookieValue ;

/**
 * Unit coverage for {@see \oihana\http\helpers\cookies\validateCookieValue()}.
 */
class ValidateCookieValueTest extends TestCase
{
    public function testEmptyValuePasses() :void
    {
        $this->expectNotToPerformAssertions() ;
        validateCookieValue( '' ) ;
    }

    public function testTypicalTokenPasses() :void
    {
        $this->expectNotToPerformAssertions() ;
        validateCookieValue( 'abc123' ) ;
        validateCookieValue( 'eyJhbGci.eyJzdWIi.signed' ) ;
        validateCookieValue( 'rt_xyz_2025' ) ;
    }

    public function testToleratedNonStrictCharactersPass() :void
    {
        // Whitespace, ", , and \ are technically RFC 6265 violations but
        // widely tolerated by browsers. Pragmatic policy: let them through.
        $this->expectNotToPerformAssertions() ;
        validateCookieValue( 'John Smith' ) ;
        validateCookieValue( '"quoted"'   ) ;
        validateCookieValue( 'a,b'        ) ;
        validateCookieValue( 'a\\b'       ) ;
    }

    public function testSemicolonThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieValue( 'foo; HttpOnly' ) ;
    }

    public function testCRLFInjectionThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieValue( "foo\r\nSet-Cookie: evil=bar" ) ;
    }

    public function testStandaloneCRThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieValue( "foo\rbar" ) ;
    }

    public function testStandaloneLFThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieValue( "foo\nbar" ) ;
    }

    public function testNullByteThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieValue( "foo\x00bar" ) ;
    }

    public function testTabThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieValue( "foo\tbar" ) ;
    }

    public function testDelCharThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieValue( "foo\x7Fbar" ) ;
    }
}
