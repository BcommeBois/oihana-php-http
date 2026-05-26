<?php

namespace tests\oihana\http\helpers\request ;

use PHPUnit\Framework\TestCase ;
use Psr\Http\Message\ServerRequestInterface ;
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\request\isAjax ;

/**
 * Unit coverage for {@see \oihana\http\helpers\request\isAjax()}.
 */
class IsAjaxTest extends TestCase
{
    private function request( array $headers = [] ) :ServerRequestInterface
    {
        $request = ( new ServerRequestFactory() )->createServerRequest( 'GET' , '/' ) ;

        foreach ( $headers as $name => $value )
        {
            $request = $request->withHeader( $name , $value ) ;
        }

        return $request ;
    }

    public function testNoHeaderReturnsFalse() :void
    {
        $this->assertFalse( isAjax( $this->request() ) ) ;
    }

    public function testXMLHttpRequestReturnsTrue() :void
    {
        $this->assertTrue
        (
            isAjax( $this->request( [ 'X-Requested-With' => 'XMLHttpRequest' ] ) )
        ) ;
    }

    public function testMatchIsCaseInsensitive() :void
    {
        $this->assertTrue
        (
            isAjax( $this->request( [ 'X-Requested-With' => 'xmlhttprequest' ] ) )
        ) ;
        $this->assertTrue
        (
            isAjax( $this->request( [ 'X-Requested-With' => 'XMLHTTPREQUEST' ] ) )
        ) ;
    }

    public function testOtherValueReturnsFalse() :void
    {
        $this->assertFalse
        (
            isAjax( $this->request( [ 'X-Requested-With' => 'fetch' ] ) )
        ) ;
    }
}
