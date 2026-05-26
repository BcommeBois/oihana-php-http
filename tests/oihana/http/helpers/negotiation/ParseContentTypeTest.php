<?php

namespace tests\oihana\http\helpers\negotiation ;

use oihana\http\enums\ContentTypeField ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\negotiation\parseContentType ;

/**
 * Unit coverage for {@see \oihana\http\helpers\negotiation\parseContentType()}.
 */
class ParseContentTypeTest extends TestCase
{
    public function testEmptyHeaderReturnsEmptyTuple() :void
    {
        $this->assertSame
        (
            [
                ContentTypeField::TYPE     => ''   ,
                ContentTypeField::CHARSET  => null ,
                ContentTypeField::BOUNDARY => null ,
                ContentTypeField::PARAMS   => []   ,
            ] ,
            parseContentType( '' ) ,
        ) ;
    }

    public function testTypeOnly() :void
    {
        $parsed = parseContentType( 'text/html' ) ;

        $this->assertSame( 'text/html' , $parsed[ ContentTypeField::TYPE     ] ) ;
        $this->assertNull (              $parsed[ ContentTypeField::CHARSET  ] ) ;
        $this->assertNull (              $parsed[ ContentTypeField::BOUNDARY ] ) ;
        $this->assertSame( []          , $parsed[ ContentTypeField::PARAMS   ] ) ;
    }

    public function testTypeIsLowercased() :void
    {
        $this->assertSame
        (
            'text/html' ,
            parseContentType( 'TEXT/HTML' )[ ContentTypeField::TYPE ] ,
        ) ;
    }

    public function testCharsetExtractedAndLowercased() :void
    {
        $parsed = parseContentType( 'text/html; charset=UTF-8' ) ;

        $this->assertSame( 'text/html' , $parsed[ ContentTypeField::TYPE     ] ) ;
        $this->assertSame( 'utf-8'     , $parsed[ ContentTypeField::CHARSET  ] ) ;
        $this->assertNull (              $parsed[ ContentTypeField::BOUNDARY ] ) ;
        $this->assertSame
        (
            [ 'charset' => 'utf-8' ] ,
            $parsed[ ContentTypeField::PARAMS ] ,
        ) ;
    }

    public function testBoundaryExtractedWithCasePreserved() :void
    {
        $parsed = parseContentType( 'multipart/form-data; boundary=---WebKitFormBoundary' ) ;

        $this->assertSame( 'multipart/form-data'    , $parsed[ ContentTypeField::TYPE     ] ) ;
        $this->assertNull (                           $parsed[ ContentTypeField::CHARSET  ] ) ;
        $this->assertSame( '---WebKitFormBoundary'  , $parsed[ ContentTypeField::BOUNDARY ] ) ;
    }

    public function testQuotedBoundaryIsUnwrapped() :void
    {
        $parsed = parseContentType( 'multipart/form-data; boundary="---abc"' ) ;
        $this->assertSame( '---abc' , $parsed[ ContentTypeField::BOUNDARY ] ) ;
    }

    public function testMultipleParameters() :void
    {
        $parsed = parseContentType( 'application/json; charset=utf-8; level=1' ) ;

        $this->assertSame
        (
            [ 'charset' => 'utf-8' , 'level' => '1' ] ,
            $parsed[ ContentTypeField::PARAMS ] ,
        ) ;
    }

    public function testParameterNamesAreLowercased() :void
    {
        $parsed = parseContentType( 'text/html; CHARSET=UTF-8; Level=1' ) ;

        $this->assertArrayHasKey( 'charset' , $parsed[ ContentTypeField::PARAMS ] ) ;
        $this->assertArrayHasKey( 'level'   , $parsed[ ContentTypeField::PARAMS ] ) ;
    }

    public function testEmptySegmentsAreSkipped() :void
    {
        $parsed = parseContentType( 'text/html;; charset=utf-8;' ) ;

        $this->assertSame( 'utf-8' , $parsed[ ContentTypeField::CHARSET ] ) ;
    }
}
