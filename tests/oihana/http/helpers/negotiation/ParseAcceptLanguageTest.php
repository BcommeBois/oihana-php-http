<?php

namespace tests\oihana\http\helpers\negotiation ;

use oihana\http\enums\AcceptField ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\negotiation\parseAcceptLanguage ;

/**
 * Unit coverage for {@see \oihana\http\helpers\negotiation\parseAcceptLanguage()}.
 *
 * Thin wrapper around `parseAcceptHeader()` — most edge cases live
 * in that test file. These tests just verify the language-specific
 * sample inputs from RFC 7231 / RFC 4647 are handled.
 */
class ParseAcceptLanguageTest extends TestCase
{
    public function testEmptyReturnsEmpty() :void
    {
        $this->assertSame( [] , parseAcceptLanguage( '' ) ) ;
    }

    public function testTypicalBrowserHeader() :void
    {
        $parsed = parseAcceptLanguage( 'fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7' ) ;

        $this->assertSame( 'fr-fr' , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 1.0     , $parsed[ 0 ][ AcceptField::QUALITY ] ) ;
        $this->assertSame( 'fr'    , $parsed[ 1 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'en-us' , $parsed[ 2 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'en'    , $parsed[ 3 ][ AcceptField::TYPE ] ) ;
    }

    public function testWildcardEntry() :void
    {
        $parsed = parseAcceptLanguage( 'fr;q=0.9, *;q=0.5' ) ;

        $this->assertSame( 'fr' , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( '*'  , $parsed[ 1 ][ AcceptField::TYPE ] ) ;
    }

    public function testScriptAndRegionSubtagsAreLowercased() :void
    {
        $parsed = parseAcceptLanguage( 'zh-Hant-CN' ) ;
        $this->assertSame( 'zh-hant-cn' , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
    }
}
