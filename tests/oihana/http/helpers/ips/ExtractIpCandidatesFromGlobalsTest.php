<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\extractIpCandidatesFromGlobals;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\extractIpCandidatesFromGlobals()}.
 *
 * Each test snapshots and restores `$_SERVER` so the global state stays
 * isolated between cases.
 */
class ExtractIpCandidatesFromGlobalsTest extends TestCase
{
    private array $backup ;

    protected function setUp(): void
    {
        $this->backup = $_SERVER ;
        $_SERVER      = [] ;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->backup ;
    }

    public function testEmptyGlobalsProducesNoCandidates(): void
    {
        [ $remote , $headers , $chain ] = extractIpCandidatesFromGlobals( false ) ;

        $this->assertNull( $remote ) ;
        $this->assertSame( [] , $headers ) ;
        $this->assertSame( [] , $chain   ) ;
    }

    public function testRemoteAddrIsSurfaced(): void
    {
        $_SERVER[ 'REMOTE_ADDR' ] = '10.0.0.1' ;

        [ $remote , $headers , $chain ] = extractIpCandidatesFromGlobals( false ) ;

        $this->assertSame( '10.0.0.1' , $remote ) ;
        $this->assertSame( [] , $headers ) ;
        $this->assertSame( [] , $chain   ) ;
    }

    public function testHeaderOrderIsCfThenXffFirstThenXRealIp(): void
    {
        $_SERVER[ 'HTTP_CF_CONNECTING_IP' ] = '8.8.8.8' ;
        $_SERVER[ 'HTTP_X_FORWARDED_FOR' ]  = '1.1.1.1, 2.2.2.2' ;
        $_SERVER[ 'HTTP_X_REAL_IP' ]        = '3.3.3.3' ;
        $_SERVER[ 'REMOTE_ADDR' ]           = '10.0.0.1' ;

        [ $remote , $headers , $chain ] = extractIpCandidatesFromGlobals( false ) ;

        $this->assertSame( '10.0.0.1' , $remote ) ;
        $this->assertSame( [ '8.8.8.8' , '1.1.1.1' , '3.3.3.3' ] , $headers ) ;
        $this->assertSame( [ '1.1.1.1' , '2.2.2.2' ] , $chain ) ;
    }

    public function testForwardedHeaderIsIgnoredWhenDisabled(): void
    {
        $_SERVER[ 'HTTP_FORWARDED' ] = 'for=8.8.8.8' ;
        $_SERVER[ 'REMOTE_ADDR' ]    = '10.0.0.1' ;

        [ , $headers , $chain ] = extractIpCandidatesFromGlobals( false ) ;

        $this->assertSame( [] , $headers ) ;
        $this->assertSame( [] , $chain   ) ;
    }

    public function testForwardedHeaderIsPrependedWhenEnabled(): void
    {
        $_SERVER[ 'HTTP_FORWARDED' ]        = 'for=8.8.8.8, for=4.4.4.4' ;
        $_SERVER[ 'HTTP_CF_CONNECTING_IP' ] = '1.1.1.1' ;
        $_SERVER[ 'HTTP_X_FORWARDED_FOR' ]  = '2.2.2.2' ;
        $_SERVER[ 'REMOTE_ADDR' ]           = '10.0.0.1' ;

        [ , $headers , $chain ] = extractIpCandidatesFromGlobals( true ) ;

        $this->assertSame( [ '8.8.8.8' , '4.4.4.4' , '1.1.1.1' , '2.2.2.2' ] , $headers ) ;
        $this->assertSame( [ '8.8.8.8' , '4.4.4.4' , '2.2.2.2' ] , $chain ) ;
    }
}
