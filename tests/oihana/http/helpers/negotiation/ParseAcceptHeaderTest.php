<?php

namespace tests\oihana\http\helpers\negotiation ;

use oihana\http\enums\AcceptField ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\negotiation\parseAcceptHeader ;

/**
 * Unit coverage for {@see \oihana\http\helpers\negotiation\parseAcceptHeader()}.
 */
class ParseAcceptHeaderTest extends TestCase
{
    public function testEmptyReturnsEmpty() :void
    {
        $this->assertSame( [] , parseAcceptHeader( '' ) ) ;
    }

    public function testWhitespaceOnlyReturnsEmpty() :void
    {
        $this->assertSame( [] , parseAcceptHeader( "   \t" ) ) ;
    }

    public function testSingleTypeDefaultsToQualityOne() :void
    {
        $this->assertSame
        (
            [
                [
                    AcceptField::TYPE    => 'text/html' ,
                    AcceptField::QUALITY => 1.0 ,
                    AcceptField::PARAMS  => [] ,
                ] ,
            ] ,
            parseAcceptHeader( 'text/html' ) ,
        ) ;
    }

    public function testTypeIsLowercased() :void
    {
        $parsed = parseAcceptHeader( 'TEXT/HTML' ) ;
        $this->assertSame( 'text/html' , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
    }

    public function testSortsByQualityDescending() :void
    {
        $parsed = parseAcceptHeader( 'text/html;q=0.5, application/json;q=1.0, */*;q=0.1' ) ;

        $this->assertSame( 'application/json' , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'text/html'        , $parsed[ 1 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( '*/*'              , $parsed[ 2 ][ AcceptField::TYPE ] ) ;
    }

    public function testStableSortPreservesOriginalOrderForEqualQuality() :void
    {
        $parsed = parseAcceptHeader( 'text/html, application/json' ) ;

        $this->assertSame( 'text/html'        , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'application/json' , $parsed[ 1 ][ AcceptField::TYPE ] ) ;
    }

    public function testQualityIsParsedAsFloat() :void
    {
        $parsed = parseAcceptHeader( 'text/html;q=0.9' ) ;
        $this->assertSame( 0.9 , $parsed[ 0 ][ AcceptField::QUALITY ] ) ;
    }

    public function testQualityIsClampedToZeroOneRange() :void
    {
        $parsed = parseAcceptHeader( 'text/html;q=2.0, application/json;q=-1' ) ;

        $this->assertSame( 1.0 , $parsed[ 0 ][ AcceptField::QUALITY ] ) ;
        $this->assertSame( 0.0 , $parsed[ 1 ][ AcceptField::QUALITY ] ) ;
    }

    public function testNonNumericQualityFallsBackToOne() :void
    {
        $parsed = parseAcceptHeader( 'text/html;q=garbage' ) ;
        $this->assertSame( 1.0 , $parsed[ 0 ][ AcceptField::QUALITY ] ) ;
    }

    public function testNonQParametersGoIntoParams() :void
    {
        $parsed = parseAcceptHeader( 'text/html;level=1;q=0.9' ) ;

        $this->assertSame( 0.9 , $parsed[ 0 ][ AcceptField::QUALITY ] ) ;
        $this->assertSame( [ 'level' => '1' ] , $parsed[ 0 ][ AcceptField::PARAMS ] ) ;
    }

    public function testQuotedParameterValuesAreUnwrapped() :void
    {
        $parsed = parseAcceptHeader( 'text/html;profile="https://example.com"' ) ;

        $this->assertSame
        (
            [ 'profile' => 'https://example.com' ] ,
            $parsed[ 0 ][ AcceptField::PARAMS ] ,
        ) ;
    }

    public function testQualityZeroIsKeptAndLandsLast() :void
    {
        $parsed = parseAcceptHeader( 'text/html;q=0, application/json' ) ;

        $this->assertCount( 2 , $parsed ) ;
        $this->assertSame( 'application/json' , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'text/html'        , $parsed[ 1 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 0.0                , $parsed[ 1 ][ AcceptField::QUALITY ] ) ;
    }

    public function testEmptySegmentsAreSkipped() :void
    {
        $parsed = parseAcceptHeader( ',,text/html, ,application/json,' ) ;

        $this->assertCount( 2 , $parsed ) ;
    }

    public function testWildcards() :void
    {
        // The parser preserves header order for entries with the same
        // q-value — it is a "raw view" of what the client said. The
        // specificity-based tie-breaking that prefers `text/html` over
        // `*/*` belongs in `negotiate()`, not in the parser.
        $parsed = parseAcceptHeader( '*/*, text/*;q=0.9, text/html' ) ;

        $this->assertSame( '*/*'        , $parsed[ 0 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'text/html'  , $parsed[ 1 ][ AcceptField::TYPE ] ) ;
        $this->assertSame( 'text/*'     , $parsed[ 2 ][ AcceptField::TYPE ] ) ;
    }
}
