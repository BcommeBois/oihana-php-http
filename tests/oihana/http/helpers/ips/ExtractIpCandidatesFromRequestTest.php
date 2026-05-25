<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

use function oihana\http\helpers\ips\extractIpCandidatesFromRequest;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\extractIpCandidatesFromRequest()}.
 */
class ExtractIpCandidatesFromRequestTest extends TestCase
{
    private function request( array $serverParams = [] , array $headers = [] ): ServerRequestInterface
    {
        $request = ( new ServerRequestFactory() )
            ->createServerRequest( 'GET' , '/' , $serverParams ) ;

        foreach ( $headers as $name => $value )
        {
            $request = $request->withHeader( $name , $value ) ;
        }

        return $request ;
    }

    public function testEmptyRequestProducesNoCandidates(): void
    {
        $request = $this->request() ;

        [ $remote , $headers , $chain ] = extractIpCandidatesFromRequest( $request , false ) ;

        $this->assertNull( $remote ) ;
        $this->assertSame( [] , $headers ) ;
        $this->assertSame( [] , $chain   ) ;
    }

    public function testRemoteAddrIsSurfaced(): void
    {
        $request = $this->request( [ 'REMOTE_ADDR' => '10.0.0.1' ] ) ;

        [ $remote , $headers , $chain ] = extractIpCandidatesFromRequest( $request , false ) ;

        $this->assertSame( '10.0.0.1' , $remote ) ;
        $this->assertSame( [] , $headers ) ;
        $this->assertSame( [] , $chain   ) ;
    }

    public function testHeaderOrderIsCfThenXffFirstThenXRealIp(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [
                'CF-Connecting-IP' => '8.8.8.8' ,
                'X-Forwarded-For'  => '1.1.1.1, 2.2.2.2' ,
                'X-Real-IP'        => '3.3.3.3' ,
            ]
        ) ;

        [ $remote , $headers , $chain ] = extractIpCandidatesFromRequest( $request , false ) ;

        $this->assertSame( '10.0.0.1' , $remote ) ;
        $this->assertSame( [ '8.8.8.8' , '1.1.1.1' , '3.3.3.3' ] , $headers ) ;
        $this->assertSame( [ '1.1.1.1' , '2.2.2.2' ] , $chain ) ;
    }

    public function testForwardedHeaderIsIgnoredWhenDisabled(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [ 'Forwarded' => 'for=8.8.8.8' ]
        ) ;

        [ , $headers , $chain ] = extractIpCandidatesFromRequest( $request , false ) ;

        $this->assertSame( [] , $headers ) ;
        $this->assertSame( [] , $chain   ) ;
    }

    public function testForwardedHeaderIsPrependedWhenEnabled(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [
                'Forwarded'        => 'for=8.8.8.8, for=4.4.4.4' ,
                'CF-Connecting-IP' => '1.1.1.1' ,
                'X-Forwarded-For'  => '2.2.2.2' ,
            ]
        ) ;

        [ , $headers , $chain ] = extractIpCandidatesFromRequest( $request , true ) ;

        $this->assertSame( [ '8.8.8.8' , '4.4.4.4' , '1.1.1.1' , '2.2.2.2' ] , $headers ) ;
        $this->assertSame( [ '8.8.8.8' , '4.4.4.4' , '2.2.2.2' ] , $chain ) ;
    }
}
