<?php

namespace tests\oihana\http\helpers ;

use oihana\http\enums\OsName ;
use PHPUnit\Framework\TestCase ;
use xyz\oihana\schema\constants\http\DeviceType ;

use function oihana\http\helpers\detectUserAgentDeviceType ;

/**
 * Unit coverage for {@see \oihana\http\helpers\detectUserAgentDeviceType()}.
 */
class DetectUserAgentDeviceTypeTest extends TestCase
{
    public function testIPadOSIsTablet() :void
    {
        $this->assertSame
        (
            DeviceType::TABLET ,
            detectUserAgentDeviceType
            (
                'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15' ,
                OsName::IPADOS ,
            ) ,
        ) ;
    }

    public function testExplicitIPadTokenIsTabletEvenWithoutOs() :void
    {
        $this->assertSame
        (
            DeviceType::TABLET ,
            detectUserAgentDeviceType( 'iPad weird tokens here' , null ) ,
        ) ;
    }

    public function testAndroidWithoutMobileTokenIsTablet() :void
    {
        $this->assertSame
        (
            DeviceType::TABLET ,
            detectUserAgentDeviceType
            (
                'Mozilla/5.0 (Linux; Android 13; SM-X510) AppleWebKit/537.36' ,
                OsName::ANDROID ,
            ) ,
        ) ;
    }

    public function testAndroidWithMobileTokenIsMobile() :void
    {
        $this->assertSame
        (
            DeviceType::MOBILE ,
            detectUserAgentDeviceType
            (
                'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 Chrome/126 Mobile Safari/537.36' ,
                OsName::ANDROID ,
            ) ,
        ) ;
    }

    public function testIOSIsMobile() :void
    {
        $this->assertSame
        (
            DeviceType::MOBILE ,
            detectUserAgentDeviceType
            (
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15' ,
                OsName::IOS ,
            ) ,
        ) ;
    }

    public function testIPhoneTokenIsMobileWithoutOs() :void
    {
        $this->assertSame
        (
            DeviceType::MOBILE ,
            detectUserAgentDeviceType( 'iPhone weird tokens' , null ) ,
        ) ;
    }

    public function testIPodTokenIsMobile() :void
    {
        $this->assertSame
        (
            DeviceType::MOBILE ,
            detectUserAgentDeviceType( 'iPod weird tokens' , null ) ,
        ) ;
    }

    public function testGenericMobileTokenWithoutOsIsMobile() :void
    {
        $this->assertSame
        (
            DeviceType::MOBILE ,
            detectUserAgentDeviceType
            (
                'KaiOS/2.5 (Mobile; rv:48.0) Gecko/48.0 Firefox/48.0' ,
                null ,
            ) ,
        ) ;
    }

    public function testWindowsIsDesktop() :void
    {
        $this->assertSame
        (
            DeviceType::DESKTOP ,
            detectUserAgentDeviceType
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' ,
                OsName::WINDOWS ,
            ) ,
        ) ;
    }

    public function testMacOSIsDesktop() :void
    {
        $this->assertSame
        (
            DeviceType::DESKTOP ,
            detectUserAgentDeviceType
            (
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15' ,
                OsName::MACOS ,
            ) ,
        ) ;
    }

    public function testLinuxDesktop() :void
    {
        $this->assertSame
        (
            DeviceType::DESKTOP ,
            detectUserAgentDeviceType
            (
                'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0' ,
                OsName::LINUX ,
            ) ,
        ) ;
    }

    public function testChromeOSIsDesktop() :void
    {
        $this->assertSame
        (
            DeviceType::DESKTOP ,
            detectUserAgentDeviceType
            (
                'Mozilla/5.0 (X11; CrOS x86_64 15917.71.0) AppleWebKit/537.36' ,
                OsName::CHROME_OS ,
            ) ,
        ) ;
    }

    public function testUnknownReturnsUnknown() :void
    {
        $this->assertSame
        (
            DeviceType::UNKNOWN ,
            detectUserAgentDeviceType( 'not-a-real-user-agent' , null ) ,
        ) ;
    }
}
