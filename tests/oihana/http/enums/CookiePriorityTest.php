<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\CookiePriority ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\CookiePriority}.
 */
class CookiePriorityTest extends TestCase
{
    public function testConstantsExposeRfcSpelling() :void
    {
        $this->assertSame( 'Low'    , CookiePriority::LOW    ) ;
        $this->assertSame( 'Medium' , CookiePriority::MEDIUM ) ;
        $this->assertSame( 'High'   , CookiePriority::HIGH   ) ;
    }

    public function testIncludesAcceptsKnownValues() :void
    {
        $this->assertTrue( CookiePriority::includes( CookiePriority::LOW    ) ) ;
        $this->assertTrue( CookiePriority::includes( CookiePriority::MEDIUM ) ) ;
        $this->assertTrue( CookiePriority::includes( CookiePriority::HIGH   ) ) ;
    }

    public function testIncludesRejectsUnknownValues() :void
    {
        $this->assertFalse( CookiePriority::includes( 'Critical' ) ) ;
        $this->assertFalse( CookiePriority::includes( 'low'      ) ) ; // case-sensitive
        $this->assertFalse( CookiePriority::includes( ''         ) ) ;
    }

    public function testEnumsListsAllThreeValues() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'Low' , 'Medium' , 'High' ] ,
            CookiePriority::enums() ,
        ) ;
    }
}
