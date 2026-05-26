<?php

namespace tests\oihana\http\helpers\request ;

use PHPUnit\Framework\TestCase ;
use Psr\Http\Message\ServerRequestInterface ;
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\request\wantsJson ;

/**
 * Unit coverage for {@see \oihana\http\helpers\request\wantsJson()}.
 */
class WantsJsonTest extends TestCase
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

    public function testNoAcceptHeaderReturnsFalse() :void
    {
        $this->assertFalse( wantsJson( $this->request() ) ) ;
    }

    public function testApplicationJsonTopReturnsTrue() :void
    {
        $this->assertTrue
        (
            wantsJson( $this->request( [ 'Accept' => 'application/json' ] ) )
        ) ;
    }

    public function testJsonBeatsHtmlByQuality() :void
    {
        $this->assertTrue
        (
            wantsJson
            (
                $this->request( [ 'Accept' => 'text/html;q=0.9, application/json' ] )
            ) ,
        ) ;
    }

    public function testHtmlBeatsJsonByQuality() :void
    {
        $this->assertFalse
        (
            wantsJson
            (
                $this->request( [ 'Accept' => 'text/html, application/json;q=0.5' ] )
            ) ,
        ) ;
    }

    public function testJsonSuffixIsRecognised() :void
    {
        // +json suffix (RFC 6839) is also a JSON intent.
        $this->assertTrue
        (
            wantsJson( $this->request( [ 'Accept' => 'application/ld+json' ] ) )
        ) ;

        $this->assertTrue
        (
            wantsJson( $this->request( [ 'Accept' => 'application/vnd.api+json' ] ) )
        ) ;
    }

    public function testTextJsonIsRecognised() :void
    {
        $this->assertTrue
        (
            wantsJson( $this->request( [ 'Accept' => 'text/json' ] ) )
        ) ;
    }

    public function testTextHtmlReturnsFalse() :void
    {
        $this->assertFalse
        (
            wantsJson( $this->request( [ 'Accept' => 'text/html' ] ) )
        ) ;
    }

    public function testWildcardReturnsFalse() :void
    {
        // */* is ambiguous — be conservative and return false rather
        // than guessing the client wants JSON.
        $this->assertFalse
        (
            wantsJson( $this->request( [ 'Accept' => '*/*' ] ) )
        ) ;
    }

    public function testXmlReturnsFalse() :void
    {
        $this->assertFalse
        (
            wantsJson( $this->request( [ 'Accept' => 'application/xml' ] ) )
        ) ;
    }

    public function testXRequestedWithAloneDoesNotImplyJson() :void
    {
        // Plain AJAX with no Accept header — wantsJson is conservative
        // and returns false. Callers needing the AJAX signal must use
        // isAjax() explicitly.
        $this->assertFalse
        (
            wantsJson( $this->request( [ 'X-Requested-With' => 'XMLHttpRequest' ] ) )
        ) ;
    }
}
