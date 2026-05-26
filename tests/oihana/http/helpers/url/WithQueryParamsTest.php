<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;
use Slim\Psr7\Uri ;

use function oihana\http\helpers\url\withQueryParams ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\withQueryParams()}.
 */
class WithQueryParamsTest extends TestCase
{
    private function uri( string $query = '' ) :Uri
    {
        return new Uri( 'https' , 'example.com' , null , '/path' , $query ) ;
    }

    public function testAddsParamToEmptyQuery() :void
    {
        $result = withQueryParams( $this->uri() , [ 'a' => '1' ] ) ;
        $this->assertSame( 'a=1' , $result->getQuery() ) ;
    }

    public function testReplacesExistingKey() :void
    {
        $result = withQueryParams
        (
            $this->uri( 'a=old' ) ,
            [ 'a' => 'new' ] ,
        ) ;
        $this->assertSame( 'a=new' , $result->getQuery() ) ;
    }

    public function testPreservesUnrelatedKeys() :void
    {
        $result = withQueryParams
        (
            $this->uri( 'a=1&b=2' ) ,
            [ 'c' => '3' ] ,
        ) ;
        // Order preserved from existing + appended new keys.
        $this->assertSame( 'a=1&b=2&c=3' , $result->getQuery() ) ;
    }

    public function testNullValueRemovesKey() :void
    {
        $result = withQueryParams
        (
            $this->uri( 'a=1&b=2' ) ,
            [ 'a' => null ] ,
        ) ;
        $this->assertSame( 'b=2' , $result->getQuery() ) ;
    }

    public function testArrayValueEmitsRepeatedKey() :void
    {
        $result = withQueryParams
        (
            $this->uri() ,
            [ 'tag' => [ 'php' , 'http' ] ] ,
        ) ;
        $this->assertSame( 'tag=php&tag=http' , $result->getQuery() ) ;
    }

    public function testBooleanIsCoercedToZeroOne() :void
    {
        $result = withQueryParams
        (
            $this->uri() ,
            [ 'verbose' => true , 'debug' => false ] ,
        ) ;
        $this->assertSame( 'verbose=1&debug=0' , $result->getQuery() ) ;
    }

    public function testReturnsNewInstance() :void
    {
        $original = $this->uri( 'a=1' ) ;
        $next     = withQueryParams( $original , [ 'b' => '2' ] ) ;

        $this->assertNotSame( $original , $next ) ;
        $this->assertSame( 'a=1' , $original->getQuery() ) ;
    }

    public function testSpaceIsRfc3986Encoded() :void
    {
        $result = withQueryParams
        (
            $this->uri() ,
            [ 'q' => 'hello world' ] ,
        ) ;
        $this->assertSame( 'q=hello%20world' , $result->getQuery() ) ;
    }
}
