<?php

namespace tests\oihana\http\helpers\auth ;

use PHPUnit\Framework\TestCase ;
use Psr\Http\Message\ServerRequestInterface ;
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\auth\getBearerToken ;

/**
 * Unit coverage for {@see \oihana\http\helpers\auth\getBearerToken()}.
 */
class GetBearerTokenTest extends TestCase
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

    public function testNoAuthorizationHeaderReturnsNull() :void
    {
        $this->assertNull( getBearerToken( $this->request() ) ) ;
    }

    public function testBearerHeaderReturnsToken() :void
    {
        $this->assertSame
        (
            'eyJhbGci.eyJzdWIi.signed' ,
            getBearerToken
            (
                $this->request( [ 'Authorization' => 'Bearer eyJhbGci.eyJzdWIi.signed' ] )
            ) ,
        ) ;
    }

    public function testSchemeMatchIsCaseInsensitive() :void
    {
        $this->assertSame
        (
            'tok' ,
            getBearerToken( $this->request( [ 'Authorization' => 'bearer tok' ] ) ) ,
        ) ;

        $this->assertSame
        (
            'tok' ,
            getBearerToken( $this->request( [ 'Authorization' => 'BEARER tok' ] ) ) ,
        ) ;
    }

    public function testBasicSchemeReturnsNull() :void
    {
        $this->assertNull
        (
            getBearerToken( $this->request( [ 'Authorization' => 'Basic dXNlcjpwYXNz' ] ) ) ,
        ) ;
    }

    public function testBearerWithEmptyCredentialsReturnsNull() :void
    {
        $this->assertNull
        (
            getBearerToken( $this->request( [ 'Authorization' => 'Bearer' ] ) ) ,
        ) ;
    }

    public function testBearerWithOnlyWhitespaceCredentialsReturnsNull() :void
    {
        $this->assertNull
        (
            getBearerToken( $this->request( [ 'Authorization' => 'Bearer   ' ] ) ) ,
        ) ;
    }
}
