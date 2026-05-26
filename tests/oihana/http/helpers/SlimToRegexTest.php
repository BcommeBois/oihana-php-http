<?php

namespace tests\oihana\http\helpers ;

use InvalidArgumentException ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\slimToRegex ;

/**
 * Unit coverage for {@see \oihana\http\helpers\slimToRegex()}.
 */
class SlimToRegexTest extends TestCase
{
    public function testStaticPath() :void
    {
        $this->assertSame
        (
            '/^\/users\/profile$/' ,
            slimToRegex( '/users/profile' ) ,
        ) ;
    }

    public function testBarePlaceholderUsesNonSlashDefault() :void
    {
        $this->assertSame
        (
            '/^\/users\/(?P<id>[^\/]+)$/' ,
            slimToRegex( '/users/{id}' ) ,
        ) ;
    }

    public function testConstrainedPlaceholder() :void
    {
        $this->assertSame
        (
            '/^\/users\/(?P<id>[0-9]+)$/' ,
            slimToRegex( '/users/{id:[0-9]+}' ) ,
        ) ;
    }

    public function testMultiplePlaceholders() :void
    {
        $this->assertSame
        (
            '/^\/users\/(?P<id>[0-9]+)\/posts\/(?P<slug>[^\/]+)$/' ,
            slimToRegex( '/users/{id:[0-9]+}/posts/{slug}' ) ,
        ) ;
    }

    public function testOptionalSegmentCompilesToNonCapturingGroup() :void
    {
        $this->assertSame
        (
            '/^\/users(?:\/(?P<id>[0-9]+))?$/' ,
            slimToRegex( '/users[/{id:[0-9]+}]' ) ,
        ) ;
    }

    public function testQuantifierBracesInsideConstraintAreNotTerminators() :void
    {
        // `{1,3}` inside the placeholder must NOT close the outer `{ip:...}`.
        $regex = slimToRegex( '/ip/{ip:[0-9]{1,3}\.[0-9]{1,3}}' ) ;

        $this->assertSame
        (
            '/^\/ip\/(?P<ip>[0-9]{1,3}\.[0-9]{1,3})$/' ,
            $regex ,
        ) ;
    }

    public function testBracketsInsideConstraintAreNotOptionalGroups() :void
    {
        // `[0-9]` is a character class, NOT a Slim optional group.
        $regex = slimToRegex( '/x/{n:[0-9]+}' ) ;

        $this->assertSame
        (
            '/^\/x\/(?P<n>[0-9]+)$/' ,
            $regex ,
        ) ;
    }

    public function testCompiledRegexActuallyMatches() :void
    {
        $regex = slimToRegex( '/users/{id:[0-9]+}' ) ;

        $this->assertSame( 1 , preg_match( $regex , '/users/42'  , $m ) ) ;
        $this->assertSame( '42' , $m[ 'id' ] ) ;

        $this->assertSame( 0 , preg_match( $regex , '/users/abc' ) ) ;
        $this->assertSame( 0 , preg_match( $regex , '/users/'    ) ) ;
        $this->assertSame( 0 , preg_match( $regex , '/posts/42'  ) ) ;
    }

    public function testCompiledOptionalRegexActuallyMatches() :void
    {
        $regex = slimToRegex( '/users[/{id:[0-9]+}]' ) ;

        $this->assertSame( 1 , preg_match( $regex , '/users'    , $m1 ) ) ;
        $this->assertSame( 1 , preg_match( $regex , '/users/42' , $m2 ) ) ;
        $this->assertSame( '42' , $m2[ 'id' ] ) ;

        $this->assertSame( 0 , preg_match( $regex , '/users/abc' ) ) ;
    }

    public function testUnmatchedOpeningBraceThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        slimToRegex( '/users/{id' ) ;
    }

    public function testEmptyPatternProducesAnchorOnly() :void
    {
        $this->assertSame( '/^$/' , slimToRegex( '' ) ) ;
    }
}
