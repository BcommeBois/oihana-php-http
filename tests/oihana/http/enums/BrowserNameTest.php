<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\BrowserName ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\BrowserName}.
 */
class BrowserNameTest extends TestCase
{
    public function testConstantsExposeCanonicalProductSpelling() :void
    {
        $this->assertSame( 'Chrome'  , BrowserName::CHROME  ) ;
        $this->assertSame( 'Edge'    , BrowserName::EDGE    ) ;
        $this->assertSame( 'Firefox' , BrowserName::FIREFOX ) ;
        $this->assertSame( 'IE'      , BrowserName::IE      ) ;
        $this->assertSame( 'Opera'   , BrowserName::OPERA   ) ;
        $this->assertSame( 'Safari'  , BrowserName::SAFARI  ) ;
        $this->assertSame( 'Vivaldi' , BrowserName::VIVALDI ) ;
    }

    public function testIncludesAcceptsKnownValues() :void
    {
        $this->assertTrue( BrowserName::includes( BrowserName::CHROME  ) ) ;
        $this->assertTrue( BrowserName::includes( BrowserName::EDGE    ) ) ;
        $this->assertTrue( BrowserName::includes( BrowserName::FIREFOX ) ) ;
        $this->assertTrue( BrowserName::includes( BrowserName::IE      ) ) ;
        $this->assertTrue( BrowserName::includes( BrowserName::OPERA   ) ) ;
        $this->assertTrue( BrowserName::includes( BrowserName::SAFARI  ) ) ;
        $this->assertTrue( BrowserName::includes( BrowserName::VIVALDI ) ) ;
    }

    public function testIncludesRejectsUnknownValues() :void
    {
        $this->assertFalse( BrowserName::includes( 'chrome'  ) ) ; // case-sensitive
        $this->assertFalse( BrowserName::includes( 'Netscape' ) ) ;
        $this->assertFalse( BrowserName::includes( ''         ) ) ;
    }

    public function testEnumsListsAllValues() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'Chrome' , 'Edge' , 'Firefox' , 'IE' , 'Opera' , 'Safari' , 'Vivaldi' ] ,
            BrowserName::enums() ,
        ) ;
    }
}
