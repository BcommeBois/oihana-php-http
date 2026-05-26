<?php

namespace tests\oihana\http\helpers ;

use InvalidArgumentException ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\expandOptionalSegments ;
use function oihana\http\helpers\slimToCasbinPattern ;

/**
 * Unit coverage for {@see \oihana\http\helpers\slimToCasbinPattern()}.
 */
class SlimToCasbinPatternTest extends TestCase
{
    public function testStaticPathIsUntouched() :void
    {
        $this->assertSame
        (
            '/static/path' ,
            slimToCasbinPattern( '/static/path' ) ,
        ) ;
    }

    public function testBarePlaceholderBecomesColonName() :void
    {
        $this->assertSame
        (
            '/users/:id' ,
            slimToCasbinPattern( '/users/{id}' ) ,
        ) ;
    }

    public function testConstrainedPlaceholderBecomesColonName() :void
    {
        $this->assertSame
        (
            '/users/:id' ,
            slimToCasbinPattern( '/users/{id:[0-9]+}' ) ,
        ) ;
    }

    public function testMultiplePlaceholders() :void
    {
        $this->assertSame
        (
            '/users/:id/posts/:slug' ,
            slimToCasbinPattern( '/users/{id:[0-9]+}/posts/{slug}' ) ,
        ) ;
    }

    public function testOptionalBracketsArePreserved() :void
    {
        $this->assertSame
        (
            '/users[/:id]' ,
            slimToCasbinPattern( '/users[/{id:[0-9]+}]' ) ,
        ) ;
    }

    public function testQuantifierBracesInConstraintDoNotBreakParsing() :void
    {
        $this->assertSame
        (
            '/ip/:ip' ,
            slimToCasbinPattern( '/ip/{ip:[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}}' ) ,
        ) ;
    }

    public function testUnmatchedOpeningBraceThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        slimToCasbinPattern( '/users/{id' ) ;
    }

    public function testEmptyPatternIsUntouched() :void
    {
        $this->assertSame( '' , slimToCasbinPattern( '' ) ) ;
    }

    public function testComposesCleanlyWithExpandOptionalSegments() :void
    {
        // The documented seeding workflow:
        // expandOptionalSegments → slimToCasbinPattern, producing
        // one canonical Casbin path per concrete route variant.
        $variants = expandOptionalSegments( '/users[/{id:[0-9]+}]' ) ;
        $canonical = array_map( 'oihana\http\helpers\slimToCasbinPattern' , $variants ) ;

        $this->assertEqualsCanonicalizing
        (
            [ '/users' , '/users/:id' ] ,
            $canonical ,
        ) ;
    }
}
