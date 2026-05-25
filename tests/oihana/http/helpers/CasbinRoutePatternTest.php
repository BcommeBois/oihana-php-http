<?php

namespace tests\oihana\http\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\casbinCanonicalisePath;

/**
 * Unit coverage for {@see \oihana\http\helpers\casbinCanonicalisePath()}.
 *
 * Exercises the pure substitution function extracted from
 * {@see \oihana\http\helpers\casbinRoutePattern()}. Standing up a real
 * Slim Route + RouteContext to test the wrapper would dominate the
 * value of the test ; the substitution is where the logic actually
 * lives, and where the regressions on `{id:regex}` and `[/{id:...}]`
 * silently bit production.
 */
class CasbinRoutePatternTest extends TestCase
{
    public function testEmptyArgumentsReturnsPathUnchanged() :void
    {
        $this->assertSame( '/customers'       , casbinCanonicalisePath( '/customers'       , [] ) ) ;
        $this->assertSame( '/customers/count' , casbinCanonicalisePath( '/customers/count' , [] ) ) ;
        $this->assertSame( '/'                , casbinCanonicalisePath( '/'                , [] ) ) ;
    }

    public function testSingleArgumentReplacesByName() :void
    {
        $this->assertSame
        (
            '/customers/:id' ,
            casbinCanonicalisePath( '/customers/123' , [ 'id' => '123' ] ) ,
        ) ;
    }

    public function testNumericLookingArgumentOnOptionalRouteWorks() :void
    {
        // The Slim raw pattern for this route is `/policies[/{id:[0-9]+}]`
        // — the brackets and regex are stripped from the canonical form
        // because we walk the resolved path, not the pattern.
        $this->assertSame
        (
            '/policies/:id' ,
            casbinCanonicalisePath( '/policies/75459030' , [ 'id' => '75459030' ] ) ,
        ) ;
    }

    public function testMultipleArgumentsAreSubstitutedIndependently() :void
    {
        $this->assertSame
        (
            '/roles/:id/permissions/:targetId' ,
            casbinCanonicalisePath
            (
                '/roles/75458220/permissions/75458898' ,
                [ 'id' => '75458220' , 'targetId' => '75458898' ] ,
            ) ,
        ) ;
    }

    public function testLiteralSegmentMatchingArgumentValueIsSafe() :void
    {
        // A literal segment that happens to equal another argument's
        // value is replaced — by design, since the walk only matches by
        // value. In practice the names + values come from Slim's actual
        // routing result, so this collision can only happen if the same
        // string genuinely appears as both a literal and an argument
        // (which would mean the route was authored ambiguously). The
        // test pins the current behaviour so a future refactor doesn't
        // silently change it.
        $this->assertSame
        (
            '/policies/:id/permissions' ,
            casbinCanonicalisePath
            (
                '/policies/42/permissions' ,
                [ 'id' => '42' ] ,
            ) ,
        ) ;
    }

    public function testEmptySegmentsAreNotMatched() :void
    {
        // explode( '/' , '/foo/' ) yields ['', 'foo', ''] — the trailing
        // empty segment must never be confused with an empty argument
        // value (defensive against synthetic args[] = '' inputs).
        $this->assertSame
        (
            '/foo/' ,
            casbinCanonicalisePath( '/foo/' , [ 'ghost' => '' ] ) ,
        ) ;
    }

    public function testRootPathIsPreserved() :void
    {
        $this->assertSame( '/' , casbinCanonicalisePath( '/' , [ 'id' => '' ] ) ) ;
    }
}
