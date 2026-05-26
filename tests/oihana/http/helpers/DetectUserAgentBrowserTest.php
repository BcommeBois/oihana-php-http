<?php

namespace tests\oihana\http\helpers ;

use oihana\http\enums\BrowserName ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\detectUserAgentBrowser ;

/**
 * Unit coverage for {@see \oihana\http\helpers\detectUserAgentBrowser()}.
 */
class DetectUserAgentBrowserTest extends TestCase
{
    public function testChromeOnWindows() :void
    {
        $this->assertSame
        (
            [ BrowserName::CHROME , '126.0.6478.127' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Safari/537.36'
            ) ,
        ) ;
    }

    public function testFirefoxOnLinux() :void
    {
        $this->assertSame
        (
            [ BrowserName::FIREFOX , '128.0' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0'
            ) ,
        ) ;
    }

    public function testSafariOnMacOSReadsVersionToken() :void
    {
        // Safari's UA carries both `Version/X.Y` (product) and
        // `Safari/Z` (WebKit build). Parser must read the product
        // version from Version/, not Safari/.
        $this->assertSame
        (
            [ BrowserName::SAFARI , '17.5' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15'
            ) ,
        ) ;
    }

    public function testEdgeIsDetectedBeforeChrome() :void
    {
        // Edge UAs carry Chrome/... — must be detected as Edge.
        $this->assertSame
        (
            [ BrowserName::EDGE , '126.0.2592.81' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.2592.81'
            ) ,
        ) ;
    }

    public function testOperaIsDetectedBeforeChrome() :void
    {
        $this->assertSame
        (
            [ BrowserName::OPERA , '111.0.0.0' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 OPR/111.0.0.0'
            ) ,
        ) ;
    }

    public function testVivaldi() :void
    {
        $this->assertSame
        (
            [ BrowserName::VIVALDI , '6.7.3329.31' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Vivaldi/6.7.3329.31'
            ) ,
        ) ;
    }

    public function testIE11ViaTridentRv() :void
    {
        $this->assertSame
        (
            [ BrowserName::IE , '11.0' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko'
            ) ,
        ) ;
    }

    public function testLegacyIE() :void
    {
        $this->assertSame
        (
            [ BrowserName::IE , '9.0' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)'
            ) ,
        ) ;
    }

    public function testChromeIOSCriOS() :void
    {
        $this->assertSame
        (
            [ BrowserName::CHROME , '126.0.6478.127' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/126.0.6478.127 Mobile/15E148 Safari/604.1'
            ) ,
        ) ;
    }

    public function testFirefoxIOSFxiOS() :void
    {
        $this->assertSame
        (
            [ BrowserName::FIREFOX , '127.0' ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/127.0 Mobile/15E148 Safari/605.1.15'
            ) ,
        ) ;
    }

    public function testSafariWithoutVersionTokenStillReturnsSafari() :void
    {
        // Pathological case: a Safari-flavoured UA without `Version/`.
        $this->assertSame
        (
            [ BrowserName::SAFARI , null ] ,
            detectUserAgentBrowser
            (
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Safari/605.1.15'
            ) ,
        ) ;
    }

    public function testUnknownReturnsBothNull() :void
    {
        $this->assertSame
        (
            [ null , null ] ,
            detectUserAgentBrowser( 'not-a-real-user-agent' ) ,
        ) ;
    }
}
