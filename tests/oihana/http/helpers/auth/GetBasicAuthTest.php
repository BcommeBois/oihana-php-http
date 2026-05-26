<?php

namespace tests\oihana\http\helpers\auth ;

use oihana\http\enums\BasicAuthField ;
use PHPUnit\Framework\TestCase ;
use Psr\Http\Message\ServerRequestInterface ;
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\auth\getBasicAuth ;

/**
 * Unit coverage for {@see \oihana\http\helpers\auth\getBasicAuth()}.
 */
class GetBasicAuthTest extends TestCase
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
        $this->assertNull( getBasicAuth( $this->request() ) ) ;
    }

    public function testValidBasicCredentials() :void
    {
        // base64('user:pass') = 'dXNlcjpwYXNz'
        $creds = getBasicAuth
        (
            $this->request( [ 'Authorization' => 'Basic dXNlcjpwYXNz' ] )
        ) ;

        $this->assertSame( 'user' , $creds[ BasicAuthField::USER ] ) ;
        $this->assertSame( 'pass' , $creds[ BasicAuthField::PASS ] ) ;
    }

    public function testSchemeMatchIsCaseInsensitive() :void
    {
        $creds = getBasicAuth
        (
            $this->request( [ 'Authorization' => 'BASIC dXNlcjpwYXNz' ] )
        ) ;

        $this->assertSame( 'user' , $creds[ BasicAuthField::USER ] ) ;
    }

    public function testBearerSchemeReturnsNull() :void
    {
        $this->assertNull
        (
            getBasicAuth
            (
                $this->request( [ 'Authorization' => 'Bearer eyJhbGci.eyJzdWIi.sig' ] )
            ) ,
        ) ;
    }

    public function testInvalidBase64ReturnsNull() :void
    {
        $this->assertNull
        (
            getBasicAuth
            (
                $this->request( [ 'Authorization' => 'Basic not_valid_base64!!!' ] )
            ) ,
        ) ;
    }

    public function testDecodedPayloadWithoutColonReturnsNull() :void
    {
        // base64('justuser') with no colon
        $payload = base64_encode( 'justuser' ) ;

        $this->assertNull
        (
            getBasicAuth
            (
                $this->request( [ 'Authorization' => 'Basic ' . $payload ] )
            ) ,
        ) ;
    }

    public function testPasswordWithEmbeddedColonIsPreserved() :void
    {
        // base64('user:my:pass:word') — only the FIRST colon counts.
        $payload = base64_encode( 'user:my:pass:word' ) ;

        $creds = getBasicAuth
        (
            $this->request( [ 'Authorization' => 'Basic ' . $payload ] )
        ) ;

        $this->assertSame( 'user'          , $creds[ BasicAuthField::USER ] ) ;
        $this->assertSame( 'my:pass:word'  , $creds[ BasicAuthField::PASS ] ) ;
    }

    public function testEmptyUserAndEmptyPassword() :void
    {
        // base64(':') = 'Og=='
        $creds = getBasicAuth
        (
            $this->request( [ 'Authorization' => 'Basic Og==' ] )
        ) ;

        $this->assertSame( '' , $creds[ BasicAuthField::USER ] ) ;
        $this->assertSame( '' , $creds[ BasicAuthField::PASS ] ) ;
    }

    public function testEmptyPasswordWithUsername() :void
    {
        // base64('admin:') = 'YWRtaW46'
        $creds = getBasicAuth
        (
            $this->request( [ 'Authorization' => 'Basic YWRtaW46' ] )
        ) ;

        $this->assertSame( 'admin' , $creds[ BasicAuthField::USER ] ) ;
        $this->assertSame( ''      , $creds[ BasicAuthField::PASS ] ) ;
    }
}
