<?php

namespace tests\oihana\http\helpers\request ;

use PHPUnit\Framework\TestCase ;
use Psr\Http\Message\ServerRequestInterface ;
use Slim\Psr7\Factory\ServerRequestFactory ;

use function oihana\http\helpers\request\isHttpsRequest ;

/**
 * Unit coverage for {@see \oihana\http\helpers\request\isHttpsRequest()}.
 */
class IsHttpsRequestTest extends TestCase
{
    private function request
    (
        string $uri              = 'http://example.com/' ,
        array  $serverParams     = [] ,
        array  $headers          = [] ,
    ) :ServerRequestInterface
    {
        $request = ( new ServerRequestFactory() )->createServerRequest
        (
            'GET' ,
            $uri ,
            $serverParams ,
        ) ;

        foreach ( $headers as $name => $value )
        {
            $request = $request->withHeader( $name , $value ) ;
        }

        return $request ;
    }

    // -----------------------------------------------------------------
    // Direct scheme
    // -----------------------------------------------------------------

    public function testDirectHttpsReturnsTrue() :void
    {
        $this->assertTrue
        (
            isHttpsRequest( $this->request( 'https://example.com/' ) )
        ) ;
    }

    public function testDirectHttpReturnsFalse() :void
    {
        $this->assertFalse
        (
            isHttpsRequest( $this->request( 'http://example.com/' ) )
        ) ;
    }

    // -----------------------------------------------------------------
    // Trusted-proxy mode
    // -----------------------------------------------------------------

    public function testForwardedProtoIgnoredWithoutTrustedProxies() :void
    {
        $this->assertFalse
        (
            isHttpsRequest
            (
                $this->request
                (
                    'http://example.com/' ,
                    [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
                    [ 'X-Forwarded-Proto' => 'https' ] ,
                ) ,
            ) ,
        ) ;
    }

    public function testForwardedProtoHonouredWhenRemoteAddrIsTrusted() :void
    {
        $this->assertTrue
        (
            isHttpsRequest
            (
                $this->request
                (
                    'http://example.com/' ,
                    [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
                    [ 'X-Forwarded-Proto' => 'https' ] ,
                ) ,
                [ '10.0.0.0/8' ] ,
            ) ,
        ) ;
    }

    public function testForwardedProtoIgnoredWhenRemoteAddrIsNotTrusted() :void
    {
        $this->assertFalse
        (
            isHttpsRequest
            (
                $this->request
                (
                    'http://example.com/' ,
                    [ 'REMOTE_ADDR' => '8.8.8.8' ] ,
                    [ 'X-Forwarded-Proto' => 'https' ] ,
                ) ,
                [ '10.0.0.0/8' ] ,
            ) ,
        ) ;
    }

    public function testForwardedProtoMatchIsCaseInsensitive() :void
    {
        $this->assertTrue
        (
            isHttpsRequest
            (
                $this->request
                (
                    'http://example.com/' ,
                    [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
                    [ 'X-Forwarded-Proto' => 'HTTPS' ] ,
                ) ,
                [ '10.0.0.0/8' ] ,
            ) ,
        ) ;
    }

    public function testForwardedProtoHttpReturnsFalse() :void
    {
        $this->assertFalse
        (
            isHttpsRequest
            (
                $this->request
                (
                    'http://example.com/' ,
                    [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
                    [ 'X-Forwarded-Proto' => 'http' ] ,
                ) ,
                [ '10.0.0.0/8' ] ,
            ) ,
        ) ;
    }

    public function testMissingRemoteAddrFallsBackToFalse() :void
    {
        $this->assertFalse
        (
            isHttpsRequest
            (
                $this->request
                (
                    'http://example.com/' ,
                    [] ,
                    [ 'X-Forwarded-Proto' => 'https' ] ,
                ) ,
                [ '10.0.0.0/8' ] ,
            ) ,
        ) ;
    }
}
