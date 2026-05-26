<?php

namespace tests\oihana\http\helpers ;

use PHPUnit\Framework\TestCase ;

use ReflectionException;
use function oihana\http\helpers\isMobileUserAgent ;

/**
 * Unit coverage for {@see isMobileUserAgent}.
 */
class IsMobileUserAgentTest extends TestCase
{
    /**
     * @return void
     * @throws ReflectionException
     */
    public function testNullIsNotMobile() :void
    {
        $this->assertFalse( isMobileUserAgent( null ) ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testEmptyIsNotMobile() :void
    {
        $this->assertFalse( isMobileUserAgent( '' ) ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testDesktopChromeIsNotMobile() :void
    {
        $this->assertFalse
        (
            isMobileUserAgent
            (
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Safari/537.36'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testDesktopMacSafariIsNotMobile() :void
    {
        $this->assertFalse
        (
            isMobileUserAgent
            (
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testIPhoneIsMobile() :void
    {
        $this->assertTrue
        (
            isMobileUserAgent
            (
                'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testAndroidPhoneIsMobile() :void
    {
        $this->assertTrue
        (
            isMobileUserAgent
            (
                'Mozilla/5.0 (Linux; Android 13; Pixel 7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Mobile Safari/537.36'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testIPadIsMobileTooByDesign() :void
    {
        // Tablets are intentionally grouped with phones — the typical
        // "serve a mobile UI?" decision puts them on the same side.
        $this->assertTrue
        (
            isMobileUserAgent
            (
                'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testAndroidTabletIsMobileTooByDesign() :void
    {
        $this->assertTrue
        (
            isMobileUserAgent
            (
                'Mozilla/5.0 (Linux; Android 13; SM-X510) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.127 Safari/537.36'
            )
        ) ;
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function testBotIsNotMobile() :void
    {
        $this->assertFalse
        (
            isMobileUserAgent
            (
                'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
            )
        ) ;
    }
}
