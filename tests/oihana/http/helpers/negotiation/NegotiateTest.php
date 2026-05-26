<?php

namespace tests\oihana\http\helpers\negotiation ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\negotiation\matchAcceptPattern ;
use function oihana\http\helpers\negotiation\negotiate ;

/**
 * Unit coverage for {@see \oihana\http\helpers\negotiation\negotiate()}
 * and {@see \oihana\http\helpers\negotiation\matchAcceptPattern()}.
 */
class NegotiateTest extends TestCase
{
    public function testEmptyAvailableReturnsDefault() :void
    {
        $this->assertSame( 'fallback' , negotiate( 'text/html' , [] , 'fallback' ) ) ;
        $this->assertNull ( negotiate( 'text/html' , [] ) ) ;
    }

    public function testEmptyAcceptReturnsDefault() :void
    {
        $this->assertSame
        (
            'fallback' ,
            negotiate( '' , [ 'text/html' ] , 'fallback' ) ,
        ) ;
    }

    public function testQualityWinsOverHeaderOrder() :void
    {
        $this->assertSame
        (
            'application/json' ,
            negotiate
            (
                'text/html;q=0.9, application/json;q=1.0' ,
                [ 'application/json' , 'text/html' ] ,
            ) ,
        ) ;
    }

    public function testFallsBackToWildcardWhenExactMissing() :void
    {
        $this->assertSame
        (
            'application/xml' ,
            negotiate
            (
                'text/html, */*;q=0.1' ,
                [ 'application/xml' ] ,
            ) ,
        ) ;
    }

    public function testWildcardSubtypeMatchesAnySubtype() :void
    {
        $this->assertSame
        (
            'text/plain' ,
            negotiate
            (
                'text/*' ,
                [ 'application/json' , 'text/plain' ] ,
            ) ,
        ) ;
    }

    public function testQualityZeroEntryIsSkipped() :void
    {
        $this->assertSame
        (
            'application/json' ,
            negotiate
            (
                'text/html;q=0, application/json' ,
                [ 'text/html' , 'application/json' ] ,
            ) ,
        ) ;
    }

    public function testTopLevelWildcardForLanguages() :void
    {
        $this->assertSame
        (
            'en' ,
            negotiate
            (
                'fr;q=0, *;q=0.5' ,
                [ 'en' , 'es' ] ,
            ) ,
        ) ;
    }

    public function testNoMatchReturnsDefault() :void
    {
        $this->assertNull
        (
            negotiate
            (
                'application/json' ,
                [ 'text/html' , 'text/plain' ] ,
            ) ,
        ) ;
    }

    public function testCandidateCasingIsPreserved() :void
    {
        // The picked candidate is returned exactly as provided by the
        // caller — even if its casing differs from the Accept header.
        $this->assertSame
        (
            'Application/JSON' ,
            negotiate
            (
                'application/json' ,
                [ 'Application/JSON' ] ,
            ) ,
        ) ;
    }

    public function testMatchAcceptPatternHandlesWildcards() :void
    {
        $this->assertTrue ( matchAcceptPattern( '*'           , 'fr-fr'        ) ) ;
        $this->assertTrue ( matchAcceptPattern( '*/*'         , 'text/html'    ) ) ;
        $this->assertTrue ( matchAcceptPattern( 'text/*'      , 'text/plain'   ) ) ;
        $this->assertFalse( matchAcceptPattern( 'text/*'      , 'image/png'    ) ) ;
        $this->assertTrue ( matchAcceptPattern( 'text/html'   , 'TEXT/HTML'    ) ) ;
        $this->assertFalse( matchAcceptPattern( 'text/html'   , 'text/plain'   ) ) ;
    }
}
