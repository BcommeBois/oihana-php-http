<?php

namespace tests\oihana\http\helpers;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\expandOptionalSegments;

/**
 * Unit coverage for {@see expandOptionalSegments()}.
 *
 * The helper expands Slim route patterns carrying top-level optional
 * bracket segments into their concrete variants, while leaving nested
 * regex character classes (inside `{...}` placeholders) untouched.
 */
class ExpandOptionalSegmentsTest extends TestCase
{
    public function testPatternWithoutOptionalSegmentIsReturnedAsIs() :void
    {
        $this->assertSame( [ '/users' ] , expandOptionalSegments( '/users' ) ) ;
        $this->assertSame( [ '/' ]      , expandOptionalSegments( '/'      ) ) ;
        $this->assertSame( [ '' ]       , expandOptionalSegments( ''       ) ) ;
    }

    public function testPlaceholderWithoutOptionalSegmentIsLeftUntouched() :void
    {
        $this->assertSame
        (
            [ '/users/{id:[0-9]+}' ] ,
            expandOptionalSegments( '/users/{id:[0-9]+}' ) ,
        ) ;
    }

    public function testSingleOptionalSegmentProducesTwoVariants() :void
    {
        $this->assertSame
        (
            [ '/users' , '/users/{id:[0-9]+}' ] ,
            expandOptionalSegments( '/users[/{id:[0-9]+}]' ) ,
        ) ;
    }

    public function testBracketsInsidePlaceholderAreNotTreatedAsOptional() :void
    {
        // `[0-9]` is a regex character class inside the `{id:...}`
        // placeholder — it must NOT be expanded as an optional segment.
        $this->assertSame
        (
            [ '/users/{id:[0-9]+}' ] ,
            expandOptionalSegments( '/users/{id:[0-9]+}' ) ,
        ) ;

        // Even with multiple character classes inside placeholders, the
        // result is a single concrete pattern.
        $this->assertSame
        (
            [ '/{slug:[a-z]+}/{id:[0-9]+}' ] ,
            expandOptionalSegments( '/{slug:[a-z]+}/{id:[0-9]+}' ) ,
        ) ;
    }

    public function testMultipleOptionalSegmentsProduceCartesianProduct() :void
    {
        $variants = expandOptionalSegments( '/a[/b][/c]' ) ;

        sort( $variants ) ;

        $this->assertSame
        (
            [ '/a' , '/a/b' , '/a/b/c' , '/a/c' ] ,
            $variants ,
        ) ;
    }

    public function testNestedOptionalSegments() :void
    {
        // `/users[/{id:[0-9]+}[/edit]]` — `edit` only appears when `id`
        // is present. Three concrete variants : no id, id alone, id+edit.
        $variants = expandOptionalSegments( '/users[/{id:[0-9]+}[/edit]]' ) ;

        sort( $variants ) ;

        $this->assertSame
        (
            [ '/users' , '/users/{id:[0-9]+}' , '/users/{id:[0-9]+}/edit' ] ,
            $variants ,
        ) ;
    }

    public function testOptionalSegmentMixedWithPlaceholderRegex() :void
    {
        // Real-world pattern produced by DocumentRoute :
        // `/policies[/{id:[0-9]+}]` — the inner `[0-9]` is a character
        // class, the outer brackets mark the optional `/id` segment.
        $this->assertSame
        (
            [ '/policies' , '/policies/{id:[0-9]+}' ] ,
            expandOptionalSegments( '/policies[/{id:[0-9]+}]' ) ,
        ) ;
    }
}
