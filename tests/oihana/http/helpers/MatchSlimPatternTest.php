<?php

namespace tests\oihana\http\helpers ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\matchSlimPattern ;

/**
 * Unit coverage for {@see \oihana\http\helpers\matchSlimPattern()}.
 */
class MatchSlimPatternTest extends TestCase
{
    public function testStaticPathExactMatch() :void
    {
        $this->assertSame( [] , matchSlimPattern( '/users' , '/users' ) ) ;
    }

    public function testStaticPathMismatchReturnsNull() :void
    {
        $this->assertNull( matchSlimPattern( '/users' , '/posts' ) ) ;
    }

    public function testBarePlaceholderCapturesValue() :void
    {
        $this->assertSame
        (
            [ 'id' => '42' ] ,
            matchSlimPattern( '/users/{id}' , '/users/42' ) ,
        ) ;
    }

    public function testConstrainedPlaceholderMatchesWhenSatisfied() :void
    {
        $this->assertSame
        (
            [ 'id' => '42' ] ,
            matchSlimPattern( '/users/{id:[0-9]+}' , '/users/42' ) ,
        ) ;
    }

    public function testConstrainedPlaceholderFailsWhenNotSatisfied() :void
    {
        $this->assertNull
        (
            matchSlimPattern( '/users/{id:[0-9]+}' , '/users/abc' ) ,
        ) ;
    }

    public function testMultiplePlaceholders() :void
    {
        $this->assertSame
        (
            [ 'id' => '42' , 'slug' => 'hello-world' ] ,
            matchSlimPattern( '/users/{id:[0-9]+}/posts/{slug}' , '/users/42/posts/hello-world' ) ,
        ) ;
    }

    public function testOptionalSegmentAbsentInPath() :void
    {
        // Empty array — pattern matched but no args captured.
        $this->assertSame
        (
            [] ,
            matchSlimPattern( '/users[/{id:[0-9]+}]' , '/users' ) ,
        ) ;
    }

    public function testOptionalSegmentPresentInPath() :void
    {
        $this->assertSame
        (
            [ 'id' => '42' ] ,
            matchSlimPattern( '/users[/{id:[0-9]+}]' , '/users/42' ) ,
        ) ;
    }

    public function testTrailingSlashIsSignificant() :void
    {
        // The regex is anchored — trailing slash matters.
        $this->assertNull
        (
            matchSlimPattern( '/users/{id}' , '/users/42/' ) ,
        ) ;
    }

    public function testIpStylePatternWithQuantifierBraces() :void
    {
        $this->assertSame
        (
            [ 'ip' => '192.168.1.42' ] ,
            matchSlimPattern
            (
                '/ip/{ip:[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}}' ,
                '/ip/192.168.1.42' ,
            ) ,
        ) ;
    }

    public function testCompletelyDifferentPathReturnsNull() :void
    {
        $this->assertNull
        (
            matchSlimPattern( '/users/{id}' , '/totally/other' ) ,
        ) ;
    }
}
