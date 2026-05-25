<?php

declare( strict_types = 1 );

namespace tests\oihana\http\helpers;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use oihana\enums\ServerParam;

use function oihana\http\helpers\getUserAgent;

#[ CoversFunction( 'oihana\\http\\helpers\\getUserAgent' ) ]
final class GetUserAgentTest extends TestCase
{
    /**
     * Snapshot of `$_SERVER` taken before each test, restored in tearDown.
     * Avoids leaking test mutations across tests when other code reads
     * `$_SERVER` directly.
     */
    private array $serverSnapshot ;

    protected function setUp(): void
    {
        $this->serverSnapshot = $_SERVER ;
        unset( $_SERVER[ ServerParam::HTTP_USER_AGENT ] );
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverSnapshot ;
    }

    public function testReturnsNullWhenServerEntryIsMissing(): void
    {
        $this->assertNull( getUserAgent() ) ;
    }

    public function testReturnsValueWhenServerEntryIsSet(): void
    {
        $_SERVER[ ServerParam::HTTP_USER_AGENT ] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)' ;
        $this->assertSame( 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)' , getUserAgent() ) ;
    }

    public function testReturnsEmptyStringAsIs(): void
    {
        $_SERVER[ ServerParam::HTTP_USER_AGENT ] = '' ;
        $this->assertSame( '' , getUserAgent() ) ;
    }

    public function testReturnsLongUserAgentVerbatim(): void
    {
        $longUa = 'Mozilla/5.0 (X11; Linux x86_64; rv:122.0) Gecko/20100101 Firefox/122.0' ;
        $_SERVER[ ServerParam::HTTP_USER_AGENT ] = $longUa ;
        $this->assertSame( $longUa , getUserAgent() ) ;
    }

    public function testHandlesBotUserAgent(): void
    {
        $_SERVER[ ServerParam::HTTP_USER_AGENT ] = 'Googlebot/2.1 (+http://www.google.com/bot.html)' ;
        $this->assertSame( 'Googlebot/2.1 (+http://www.google.com/bot.html)' , getUserAgent() ) ;
    }

    public function testHandlesCurlUserAgent(): void
    {
        $_SERVER[ ServerParam::HTTP_USER_AGENT ] = 'curl/8.7.1' ;
        $this->assertSame( 'curl/8.7.1' , getUserAgent() ) ;
    }

    public function testHandlesUserAgentWithUnicode(): void
    {
        $ua = 'MyApp/1.0 (Çàéüñ; build 42)' ;
        $_SERVER[ ServerParam::HTTP_USER_AGENT ] = $ua ;
        $this->assertSame( $ua , getUserAgent() ) ;
    }

    public function testServerParamKeyUsedIsHttpUserAgent(): void
    {
        $this->assertSame( 'HTTP_USER_AGENT' , ServerParam::HTTP_USER_AGENT ) ;
    }
}
