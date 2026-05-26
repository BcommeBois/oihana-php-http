<?php

namespace tests\oihana\http\helpers ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\detectUserAgentBot ;

/**
 * Unit coverage for {@see \oihana\http\helpers\detectUserAgentBot()}.
 */
class DetectUserAgentBotTest extends TestCase
{
    public function testRegularBrowserIsNotABot() :void
    {
        $this->assertFalse
        (
            detectUserAgentBot
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Safari/537.36'
            )
        ) ;
    }

    public function testGooglebotIsDetectedViaGenericToken() :void
    {
        $this->assertTrue
        (
            detectUserAgentBot
            (
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
            )
        ) ;
    }

    public function testBingbotIsDetectedViaGenericToken() :void
    {
        $this->assertTrue
        (
            detectUserAgentBot
            (
                'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'
            )
        ) ;
    }

    public function testBaiduSpiderIsDetectedViaGenericToken() :void
    {
        $this->assertTrue
        (
            detectUserAgentBot
            (
                'Mozilla/5.0 (compatible; Baiduspider/2.0; +http://www.baidu.com/search/spider.html)'
            )
        ) ;
    }

    public function testCurlIsDetectedViaNamedList() :void
    {
        $this->assertTrue( detectUserAgentBot( 'curl/8.4.0' ) ) ;
    }

    public function testWgetIsDetectedViaNamedList() :void
    {
        $this->assertTrue( detectUserAgentBot( 'Wget/1.21.4' ) ) ;
    }

    public function testPythonRequestsIsDetectedViaNamedList() :void
    {
        $this->assertTrue( detectUserAgentBot( 'python-requests/2.31.0' ) ) ;
    }

    public function testHeadlessChromeIsDetectedViaNamedList() :void
    {
        $this->assertTrue
        (
            detectUserAgentBot
            (
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/126.0.0.0 Safari/537.36'
            )
        ) ;
    }

    public function testFacebookExternalHitIsDetectedViaNamedList() :void
    {
        $this->assertTrue
        (
            detectUserAgentBot
            (
                'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'
            )
        ) ;
    }

    public function testGarbageStringIsNotABot() :void
    {
        $this->assertFalse( detectUserAgentBot( 'not-a-real-user-agent' ) ) ;
    }
}
