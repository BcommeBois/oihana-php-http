<?php

namespace tests\oihana\http\helpers ;

use PHPUnit\Framework\TestCase ;

use ReflectionException;

use function oihana\http\helpers\isBotUserAgent ;

/**
 * Unit coverage for {@see \oihana\http\helpers\isBotUserAgent()}.
 */
class IsBotUserAgentTest extends TestCase
{
    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNullIsNotABot() :void
    {
        $this->assertFalse( isBotUserAgent( null ) ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testEmptyIsNotABot() :void
    {
        $this->assertFalse( isBotUserAgent( '' ) ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testRegularBrowserIsNotABot() :void
    {
        $this->assertFalse
        (
            isBotUserAgent
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Safari/537.36'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testGooglebotIsABot() :void
    {
        $this->assertTrue
        (
            isBotUserAgent
            (
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testCurlIsABot() :void
    {
        $this->assertTrue( isBotUserAgent( 'curl/8.4.0' ) ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testWgetIsABot() :void
    {
        $this->assertTrue( isBotUserAgent( 'Wget/1.21.4' ) ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testHeadlessChromeIsABot() :void
    {
        $this->assertTrue
        (
            isBotUserAgent
            (
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/126.0.0.0 Safari/537.36'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testPythonRequestsIsABot() :void
    {
        $this->assertTrue( isBotUserAgent( 'python-requests/2.31.0' ) ) ;
    }
}
