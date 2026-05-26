<?php

namespace tests\oihana\http\helpers\cookies ;

use InvalidArgumentException ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\cookies\validateCookieName ;

/**
 * Unit coverage for {@see \oihana\http\helpers\cookies\validateCookieName()}.
 */
class ValidateCookieNameTest extends TestCase
{
    public function testTypicalNamePasses() :void
    {
        $this->expectNotToPerformAssertions() ;
        validateCookieName( 'access_token'  ) ;
        validateCookieName( 'refresh_token' ) ;
        validateCookieName( 'a'             ) ;
        validateCookieName( 'PHPSESSID'     ) ;
        validateCookieName( '__Host-id'     ) ;
    }

    public function testAllTokenSymbolsPass() :void
    {
        $this->expectNotToPerformAssertions() ;
        validateCookieName( "!#\$%&'*+-.^_`|~" ) ;
    }

    public function testEmptyNameThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        $this->expectExceptionMessage( 'must not be empty' ) ;
        validateCookieName( '' ) ;
    }

    public function testNameWithSpaceThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieName( 'foo bar' ) ;
    }

    public function testNameWithSemicolonThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieName( 'foo;bar' ) ;
    }

    public function testNameWithEqualsThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieName( 'foo=bar' ) ;
    }

    public function testNameWithCommaThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieName( 'foo,bar' ) ;
    }

    public function testNameWithCRLFThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieName( "foo\r\nbar" ) ;
    }

    public function testNameWithControlCharThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieName( "foo\x01bar" ) ;
    }

    public function testNameWithBraceThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieName( 'foo{bar}' ) ;
    }

    public function testNameWithBracketThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        validateCookieName( 'foo[bar]' ) ;
    }
}
