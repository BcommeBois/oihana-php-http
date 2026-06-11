<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

use function oihana\http\helpers\ips\getClientIp;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\getClientIp()}.
 *
 * Two-axis test matrix:
 * - Default behaviour (no options) — preserves the legacy header order
 *   used by every existing caller.
 * - Trusted-proxy mode + RFC 7239 + private-range filter.
 */
class GetClientIpTest extends TestCase
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

    // =========================================================
    // LEGACY MODE — no options, must match the previous helper
    // =========================================================

    public function testCloudflareHeaderHasHighestPriority(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [
                'CF-Connecting-IP' => '8.8.8.8' ,
                'X-Forwarded-For'  => '1.1.1.1, 4.4.4.4' ,
                'X-Real-IP'        => '2.2.2.2' ,
            ]
        ) ;

        $this->assertSame( '8.8.8.8' , getClientIp( $request ) ) ;
    }

    public function testXForwardedForFirstEntryWinsWhenNoCloudflare(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [
                'X-Forwarded-For' => '1.1.1.1, 4.4.4.4' ,
                'X-Real-IP'       => '2.2.2.2' ,
            ]
        ) ;

        $this->assertSame( '1.1.1.1' , getClientIp( $request ) ) ;
    }

    public function testXRealIpUsedWhenNoForwarded(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [ 'X-Real-IP' => '2.2.2.2' ]
        ) ;

        $this->assertSame( '2.2.2.2' , getClientIp( $request ) ) ;
    }

    public function testRemoteAddrFallback(): void
    {
        $request = $this->request( [ 'REMOTE_ADDR' => '10.0.0.42' ] ) ;

        $this->assertSame( '10.0.0.42' , getClientIp( $request ) ) ;
    }

    public function testNoRequestNoServerReturnsNull(): void
    {
        $backup  = $_SERVER ;
        $_SERVER = [] ;

        try
        {
            $this->assertNull( getClientIp() ) ;
        }
        finally
        {
            $_SERVER = $backup ;
        }
    }

    public function testGlobalsFallbackXForwardedFor(): void
    {
        $backup  = $_SERVER ;
        $_SERVER = [
            'HTTP_X_FORWARDED_FOR' => '1.2.3.4, 5.6.7.8' ,
            'REMOTE_ADDR'          => '10.0.0.1' ,
        ] ;

        try
        {
            $this->assertSame( '1.2.3.4' , getClientIp() ) ;
        }
        finally
        {
            $_SERVER = $backup ;
        }
    }

    public function testInvalidCfHeaderFallsBackToXForwardedFor(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [
                'CF-Connecting-IP' => 'not-an-ip' ,
                'X-Forwarded-For'  => '8.8.8.8' ,
            ]
        ) ;

        $this->assertSame( '8.8.8.8' , getClientIp( $request ) ) ;
    }

    public function testCanonicalisationStripsIpv4MappedIpv6(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '127.0.0.1' ] ,
            [ 'CF-Connecting-IP' => '::ffff:192.168.1.10' ]
        ) ;

        $this->assertSame( '192.168.1.10' , getClientIp( $request ) ) ;
    }

    public function testForwardedHeaderIsIgnoredByDefault(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [ 'Forwarded' => 'for=8.8.8.8' ]
        ) ;

        $this->assertSame( '10.0.0.1' , getClientIp( $request ) ) ;
    }

    // =========================================================
    // FORWARDED MODE (RFC 7239)
    // =========================================================

    public function testForwardedHeaderHonouredWhenEnabled(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [ 'Forwarded' => 'for=8.8.8.8;proto=https' ]
        ) ;

        $this->assertSame( '8.8.8.8' , getClientIp( $request , useForwarded: true ) ) ;
    }

    public function testForwardedTakesPriorityOverCloudflare(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.1' ] ,
            [
                'Forwarded'        => 'for=1.1.1.1' ,
                'CF-Connecting-IP' => '8.8.8.8' ,
            ]
        ) ;

        $this->assertSame( '1.1.1.1' , getClientIp( $request , useForwarded: true ) ) ;
    }

    // =========================================================
    // TRUSTED PROXIES
    // =========================================================

    public function testUntrustedDirectHopIgnoresHeaders(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '203.0.113.5' ] ,
            [
                'CF-Connecting-IP' => '8.8.8.8' ,
                'X-Forwarded-For'  => '1.2.3.4' ,
            ]
        ) ;

        $this->assertSame
        (
            '203.0.113.5' ,
            getClientIp( $request , trustedProxies: [ '127.0.0.1' , '10.0.0.0/8' ] )
        ) ;
    }

    public function testTrustedDirectHopFollowsXForwardedForRightToLeft(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.5' ] ,
            [ 'X-Forwarded-For' => '8.8.8.8, 10.0.0.99, 10.0.0.5' ]
        ) ;

        $this->assertSame
        (
            '8.8.8.8' ,
            getClientIp( $request , trustedProxies: [ '10.0.0.0/8' ] )
        ) ;
    }

    public function testTrustedHopWithFullyTrustedChainFallsBackToHeaders(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '10.0.0.5' ] ,
            [
                'X-Forwarded-For'  => '10.0.0.99, 10.0.0.5' ,
                'CF-Connecting-IP' => '8.8.8.8' ,
            ]
        ) ;

        $this->assertSame
        (
            '8.8.8.8' ,
            getClientIp( $request , trustedProxies: [ '10.0.0.0/8' ] )
        ) ;
    }

    public function testTrustedHopWithNoForwardingFallsBackToRemoteAddr(): void
    {
        // Trusted direct hop, but no X-Forwarded-For / Forwarded and no
        // other proxy header → the walk yields nothing, the header loop is
        // empty, and the helper falls back to REMOTE_ADDR itself.
        $request = $this->request( [ 'REMOTE_ADDR' => '10.0.0.5' ] ) ;

        $this->assertSame
        (
            '10.0.0.5' ,
            getClientIp( $request , trustedProxies: [ '10.0.0.0/8' ] )
        ) ;
    }

    public function testTrustedProxyAcceptsBareIp(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '127.0.0.1' ] ,
            [ 'X-Forwarded-For' => '8.8.8.8, 127.0.0.1' ]
        ) ;

        $this->assertSame
        (
            '8.8.8.8' ,
            getClientIp( $request , trustedProxies: [ '127.0.0.1' ] )
        ) ;
    }

    // =========================================================
    // ALLOW-PRIVATE FILTER
    // =========================================================

    public function testAllowPrivateFalseRejectsPrivateRemoteAddr(): void
    {
        $request = $this->request( [ 'REMOTE_ADDR' => '10.0.0.42' ] ) ;

        $this->assertNull( getClientIp( $request , allowPrivate: false ) ) ;
    }

    public function testAllowPrivateFalseSkipsPrivateHeaderAndReturnsNullWhenAllPrivate(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '127.0.0.1' ] ,
            [
                'CF-Connecting-IP' => '192.168.1.10' ,
                'X-Forwarded-For'  => '10.0.0.5' ,
            ]
        ) ;

        $this->assertNull( getClientIp( $request , allowPrivate: false ) ) ;
    }

    public function testAllowPrivateFalseSkipsPrivatesAndAcceptsFirstPublic(): void
    {
        $request = $this->request
        (
            [ 'REMOTE_ADDR' => '127.0.0.1' ] ,
            [
                'CF-Connecting-IP' => '192.168.1.10' ,
                'X-Forwarded-For'  => '8.8.8.8' ,
            ]
        ) ;

        $this->assertSame( '8.8.8.8' , getClientIp( $request , allowPrivate: false ) ) ;
    }
}
