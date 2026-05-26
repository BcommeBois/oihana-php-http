<?php

namespace tests\oihana\http\helpers ;

use oihana\http\enums\BrowserName ;
use oihana\http\enums\OsName ;
use PHPUnit\Framework\TestCase ;
use xyz\oihana\schema\constants\http\DeviceType ;
use xyz\oihana\schema\http\UserAgentInfo ;

use function oihana\http\helpers\parseUserAgent ;

/**
 * Integration coverage for {@see \oihana\http\helpers\parseUserAgent()}.
 *
 * Asserts the orchestrator wires the four detection helpers
 * ({@see detectUserAgentBot}, {@see detectUserAgentBrowser},
 * {@see detectUserAgentOs}, {@see detectUserAgentDeviceType})
 * into a coherent {@see UserAgentInfo} DTO. Detection edge cases
 * live in the per-helper test files.
 */
class ParseUserAgentTest extends TestCase
{
    public function testNullInputReturnsEmptyInfo() :void
    {
        $info = parseUserAgent( null ) ;

        $this->assertNull ( $info->raw ?? null ) ;
        $this->assertNull ( $info->browser ?? null ) ;
        $this->assertNull ( $info->os ?? null ) ;
        $this->assertSame ( DeviceType::UNKNOWN , $info->deviceType ) ;
        $this->assertFalse( $info->isBot ) ;
    }

    public function testEmptyInputReturnsEmptyInfo() :void
    {
        $info = parseUserAgent( '' ) ;

        $this->assertSame ( '' , $info->raw ) ;
        $this->assertSame ( DeviceType::UNKNOWN , $info->deviceType ) ;
        $this->assertFalse( $info->isBot ) ;
    }

    public function testChromeOnWindowsDesktop() :void
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Safari/537.36' ;
        $info = parseUserAgent( $ua ) ;

        $this->assertSame ( BrowserName::CHROME  , $info->browser ) ;
        $this->assertSame ( '126.0.6478.127'     , $info->browserVersion ) ;
        $this->assertSame ( OsName::WINDOWS      , $info->os ) ;
        $this->assertSame ( '10'                 , $info->osVersion ) ;
        $this->assertSame ( DeviceType::DESKTOP  , $info->deviceType ) ;
        $this->assertFalse( $info->isBot ) ;
        $this->assertSame ( $ua , $info->raw ) ;
    }

    public function testFirefoxOnLinux() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0'
        ) ;

        $this->assertSame ( BrowserName::FIREFOX , $info->browser ) ;
        $this->assertSame ( '128.0'              , $info->browserVersion ) ;
        $this->assertSame ( OsName::LINUX        , $info->os ) ;
        $this->assertSame ( DeviceType::DESKTOP  , $info->deviceType ) ;
        $this->assertFalse( $info->isBot ) ;
    }

    public function testSafariOnMacOS() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15'
        ) ;

        $this->assertSame ( BrowserName::SAFARI , $info->browser ) ;
        $this->assertSame ( '17.5'              , $info->browserVersion ) ;
        $this->assertSame ( OsName::MACOS       , $info->os ) ;
        $this->assertSame ( '14.5'              , $info->osVersion ) ;
        $this->assertSame ( DeviceType::DESKTOP , $info->deviceType ) ;
    }

    public function testEdgeOnWindows() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 Edg/126.0.2592.81'
        ) ;

        $this->assertSame ( BrowserName::EDGE   , $info->browser ) ;
        $this->assertSame ( '126.0.2592.81'     , $info->browserVersion ) ;
        $this->assertSame ( OsName::WINDOWS     , $info->os ) ;
        $this->assertSame ( DeviceType::DESKTOP , $info->deviceType ) ;
    }

    public function testOperaOnWindows() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36 OPR/111.0.0.0'
        ) ;

        $this->assertSame( BrowserName::OPERA , $info->browser ) ;
        $this->assertSame( '111.0.0.0'        , $info->browserVersion ) ;
    }

    public function testIPhoneSafariIsMobileiOS() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'
        ) ;

        $this->assertSame( BrowserName::SAFARI , $info->browser ) ;
        $this->assertSame( '17.5'              , $info->browserVersion ) ;
        $this->assertSame( OsName::IOS         , $info->os ) ;
        $this->assertSame( '17.5.1'            , $info->osVersion ) ;
        $this->assertSame( DeviceType::MOBILE  , $info->deviceType ) ;
    }

    public function testIPadIsTablet() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'
        ) ;

        $this->assertSame( OsName::IPADOS     , $info->os ) ;
        $this->assertSame( '17.5'             , $info->osVersion ) ;
        $this->assertSame( DeviceType::TABLET , $info->deviceType ) ;
    }

    public function testAndroidPhoneIsMobile() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Mobile Safari/537.36'
        ) ;

        $this->assertSame( BrowserName::CHROME , $info->browser ) ;
        $this->assertSame( OsName::ANDROID     , $info->os ) ;
        $this->assertSame( '13'                , $info->osVersion ) ;
        $this->assertSame( DeviceType::MOBILE  , $info->deviceType ) ;
    }

    public function testAndroidTabletIsTablet() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (Linux; Android 13; SM-X510) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Safari/537.36'
        ) ;

        $this->assertSame( OsName::ANDROID    , $info->os ) ;
        $this->assertSame( DeviceType::TABLET , $info->deviceType ) ;
    }

    public function testChromeOSDesktop() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (X11; CrOS x86_64 15917.71.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36'
        ) ;

        $this->assertSame( OsName::CHROME_OS    , $info->os ) ;
        $this->assertSame( DeviceType::DESKTOP , $info->deviceType ) ;
    }

    public function testGooglebotIsBot() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
        ) ;

        $this->assertTrue( $info->isBot ) ;
        $this->assertSame( DeviceType::BOT , $info->deviceType ) ;
    }

    public function testBingbotIsBot() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)'
        ) ;

        $this->assertTrue( $info->isBot ) ;
        $this->assertSame( DeviceType::BOT , $info->deviceType ) ;
    }

    public function testCurlIsBot() :void
    {
        $info = parseUserAgent( 'curl/8.4.0' ) ;

        $this->assertTrue( $info->isBot ) ;
    }

    public function testHeadlessChromeIsBot() :void
    {
        $info = parseUserAgent
        (
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) HeadlessChrome/126.0.0.0 Safari/537.36'
        ) ;

        $this->assertTrue( $info->isBot ) ;
    }

    public function testFacebookExternalHitIsBot() :void
    {
        $info = parseUserAgent
        (
            'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)'
        ) ;

        $this->assertTrue( $info->isBot ) ;
    }

    public function testRawIsPreservedVerbatim() :void
    {
        $ua = 'SomeCustomAgent/1.0 (with weird tokens; here)' ;
        $this->assertSame( $ua , parseUserAgent( $ua )->raw ) ;
    }

    public function testGarbageInputProducesUnknownDeviceWithRaw() :void
    {
        $info = parseUserAgent( 'not-a-real-user-agent' ) ;

        $this->assertNull ( $info->browser ?? null ) ;
        $this->assertNull ( $info->os ?? null ) ;
        $this->assertSame ( DeviceType::UNKNOWN , $info->deviceType ) ;
        $this->assertFalse( $info->isBot ) ;
        $this->assertSame ( 'not-a-real-user-agent' , $info->raw ) ;
    }

    public function testReturnsUserAgentInfoInstance() :void
    {
        $this->assertInstanceOf
        (
            UserAgentInfo::class ,
            parseUserAgent( 'Mozilla/5.0' ) ,
        ) ;
    }
}
