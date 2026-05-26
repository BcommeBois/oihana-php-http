<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;
use Slim\Psr7\Uri ;

use function oihana\http\helpers\url\removeQueryParam ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\removeQueryParam()}.
 */
class RemoveQueryParamTest extends TestCase
{
    private function uri( string $query = '' ) :Uri
    {
        return new Uri( 'https' , 'example.com' , null , '/path' , $query ) ;
    }

    public function testRemovesExistingKey() :void
    {
        $result = removeQueryParam
        (
            $this->uri( 'a=1&b=2' ) ,
            'a' ,
        ) ;
        $this->assertSame( 'b=2' , $result->getQuery() ) ;
    }

    public function testAbsentKeyIsNoOp() :void
    {
        $result = removeQueryParam
        (
            $this->uri( 'a=1' ) ,
            'unknown' ,
        ) ;
        $this->assertSame( 'a=1' , $result->getQuery() ) ;
    }

    public function testEmptyQueryRemainsEmpty() :void
    {
        $result = removeQueryParam( $this->uri() , 'a' ) ;
        $this->assertSame( '' , $result->getQuery() ) ;
    }

    public function testRemovesAllValuesOfMultiValuedKey() :void
    {
        $result = removeQueryParam
        (
            $this->uri( 'tag=php&tag=http&keep=yes' ) ,
            'tag' ,
        ) ;
        $this->assertSame( 'keep=yes' , $result->getQuery() ) ;
    }

    public function testReturnsNewInstance() :void
    {
        $original = $this->uri( 'a=1&b=2' ) ;
        $next     = removeQueryParam( $original , 'a' ) ;

        $this->assertNotSame( $original , $next ) ;
        $this->assertSame( 'a=1&b=2' , $original->getQuery() ) ;
    }
}
