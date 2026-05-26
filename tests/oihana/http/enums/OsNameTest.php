<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\OsName ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\OsName}.
 */
class OsNameTest extends TestCase
{
    public function testConstantsExposeCanonicalNames() :void
    {
        $this->assertSame( 'Android'  , OsName::ANDROID  ) ;
        $this->assertSame( 'ChromeOS' , OsName::CHROME_OS ) ;
        $this->assertSame( 'iOS'      , OsName::IOS      ) ;
        $this->assertSame( 'iPadOS'   , OsName::IPADOS   ) ;
        $this->assertSame( 'Linux'    , OsName::LINUX    ) ;
        $this->assertSame( 'macOS'    , OsName::MACOS    ) ;
        $this->assertSame( 'Windows'  , OsName::WINDOWS  ) ;
    }

    public function testIncludesAcceptsKnownValues() :void
    {
        $this->assertTrue( OsName::includes( OsName::ANDROID  ) ) ;
        $this->assertTrue( OsName::includes( OsName::CHROME_OS ) ) ;
        $this->assertTrue( OsName::includes( OsName::IOS      ) ) ;
        $this->assertTrue( OsName::includes( OsName::IPADOS   ) ) ;
        $this->assertTrue( OsName::includes( OsName::LINUX    ) ) ;
        $this->assertTrue( OsName::includes( OsName::MACOS    ) ) ;
        $this->assertTrue( OsName::includes( OsName::WINDOWS  ) ) ;
    }

    public function testIncludesRejectsUnknownValues() :void
    {
        $this->assertFalse( OsName::includes( 'MACOS'     ) ) ; // case-sensitive
        $this->assertFalse( OsName::includes( 'FreeBSD'   ) ) ;
        $this->assertFalse( OsName::includes( ''          ) ) ;
    }

    public function testEnumsListsAllValues() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'Android' , 'ChromeOS' , 'iOS' , 'iPadOS' , 'Linux' , 'macOS' , 'Windows' ] ,
            OsName::enums() ,
        ) ;
    }
}
