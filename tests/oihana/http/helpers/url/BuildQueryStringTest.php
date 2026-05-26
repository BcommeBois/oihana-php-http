<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\url\buildQueryString ;
use function oihana\http\helpers\url\parseQueryString ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\buildQueryString()}.
 */
class BuildQueryStringTest extends TestCase
{
    public function testEmptyArrayReturnsEmpty() :void
    {
        $this->assertSame( '' , buildQueryString( [] ) ) ;
    }

    public function testSinglePair() :void
    {
        $this->assertSame
        (
            'a=1' ,
            buildQueryString( [ 'a' => '1' ] ) ,
        ) ;
    }

    public function testMultiplePairsAreAmpersandSeparated() :void
    {
        $this->assertSame
        (
            'a=1&b=2' ,
            buildQueryString( [ 'a' => '1' , 'b' => '2' ] ) ,
        ) ;
    }

    public function testArrayValueEmitsRepeatedKeys() :void
    {
        $this->assertSame
        (
            'a=1&a=2&a=3' ,
            buildQueryString( [ 'a' => [ '1' , '2' , '3' ] ] ) ,
        ) ;
    }

    public function testRfc3986EncodingByDefault() :void
    {
        $this->assertSame
        (
            'q=hello%20world' ,
            buildQueryString( [ 'q' => 'hello world' ] ) ,
        ) ;
    }

    public function testFormEncodingWhenDisabled() :void
    {
        $this->assertSame
        (
            'q=hello+world' ,
            buildQueryString( [ 'q' => 'hello world' ] , false ) ,
        ) ;
    }

    public function testBooleanTrueEmitsOne() :void
    {
        $this->assertSame
        (
            'verbose=1' ,
            buildQueryString( [ 'verbose' => true ] ) ,
        ) ;
    }

    public function testBooleanFalseEmitsZero() :void
    {
        $this->assertSame
        (
            'verbose=0' ,
            buildQueryString( [ 'verbose' => false ] ) ,
        ) ;
    }

    public function testNullValueEmitsBareKey() :void
    {
        $this->assertSame
        (
            'flag' ,
            buildQueryString( [ 'flag' => null ] ) ,
        ) ;
    }

    public function testIntegerValue() :void
    {
        $this->assertSame
        (
            'count=42' ,
            buildQueryString( [ 'count' => 42 ] ) ,
        ) ;
    }

    public function testRoundtripWithParseQueryString() :void
    {
        $original = [
            'a' => [ '1' , '2' ] ,
            'b' => [ 'hello world' ] ,
            'c' => [ '' ] ,
        ] ;

        $rebuilt = parseQueryString( buildQueryString( $original ) ) ;

        $this->assertSame( $original , $rebuilt ) ;
    }
}
