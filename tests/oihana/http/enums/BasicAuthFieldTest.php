<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\BasicAuthField ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\BasicAuthField}.
 */
class BasicAuthFieldTest extends TestCase
{
    public function testConstantsExposeLowercaseKeys() :void
    {
        $this->assertSame( 'pass' , BasicAuthField::PASS ) ;
        $this->assertSame( 'user' , BasicAuthField::USER ) ;
    }

    public function testEnumsListsAllValues() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'pass' , 'user' ] ,
            BasicAuthField::enums() ,
        ) ;
    }
}
