<?php

namespace tests\oihana\http\helpers ;

use oihana\http\enums\OsName ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\detectUserAgentOs ;

/**
 * Unit coverage for {@see \oihana\http\helpers\detectUserAgentOs()}.
 */
class DetectUserAgentOsTest extends TestCase
{
    public function testWindows10IsRemappedFromNT10() :void
    {
        $this->assertSame
        (
            [ OsName::WINDOWS , '10' ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ) ,
        ) ;
    }

    public function testWindows7IsRemappedFromNT61() :void
    {
        $this->assertSame
        (
            [ OsName::WINDOWS , '7' ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (Windows NT 6.1) Gecko/20100101'
            ) ,
        ) ;
    }

    public function testWindows81IsRemappedFromNT63() :void
    {
        $this->assertSame
        (
            [ OsName::WINDOWS , '8.1' ] ,
            detectUserAgentOs( 'Mozilla/5.0 (Windows NT 6.3) Gecko/20100101' ) ,
        ) ;
    }

    public function testWindowsUnknownNTVersionFallsBackToRawNumber() :void
    {
        // NT 99.0 doesn't exist — parser keeps it as-is.
        $this->assertSame
        (
            [ OsName::WINDOWS , '99.0' ] ,
            detectUserAgentOs( 'Mozilla/5.0 (Windows NT 99.0)' ) ,
        ) ;
    }

    public function testMacOSWithVersionNormalisesUnderscores() :void
    {
        $this->assertSame
        (
            [ OsName::MACOS , '14.5' ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15'
            ) ,
        ) ;
    }

    public function testMacOSWithoutVersion() :void
    {
        $this->assertSame
        (
            [ OsName::MACOS , null ] ,
            detectUserAgentOs( 'Mozilla/5.0 (Macintosh) AppleWebKit/605.1.15' ) ,
        ) ;
    }

    public function testIPadIsDetectedBeforeIPhone() :void
    {
        $this->assertSame
        (
            [ OsName::IPADOS , '17.5' ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15'
            ) ,
        ) ;
    }

    public function testIPhone() :void
    {
        $this->assertSame
        (
            [ OsName::IOS , '17.5.1' ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15'
            ) ,
        ) ;
    }

    public function testIPod() :void
    {
        $this->assertSame
        (
            [ OsName::IOS , '15.0' ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (iPod touch; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15'
            ) ,
        ) ;
    }

    public function testAndroidIsDetectedBeforeLinux() :void
    {
        // Android UAs contain "Linux" too — Android must win.
        $this->assertSame
        (
            [ OsName::ANDROID , '13' ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36'
            ) ,
        ) ;
    }

    public function testChromeOS() :void
    {
        $this->assertSame
        (
            [ OsName::CHROME_OS , null ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (X11; CrOS x86_64 15917.71.0) AppleWebKit/537.36'
            ) ,
        ) ;
    }

    public function testPlainLinuxFallback() :void
    {
        $this->assertSame
        (
            [ OsName::LINUX , null ] ,
            detectUserAgentOs
            (
                'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0'
            ) ,
        ) ;
    }

    public function testUnknownReturnsBothNull() :void
    {
        $this->assertSame
        (
            [ null , null ] ,
            detectUserAgentOs( 'not-a-real-user-agent' ) ,
        ) ;
    }
}
