<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\AcceptField ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\AcceptField}.
 */
class AcceptFieldTest extends TestCase
{
    public function testConstantsExposeLowercaseKeys() :void
    {
        $this->assertSame( 'params'  , AcceptField::PARAMS  ) ;
        $this->assertSame( 'quality' , AcceptField::QUALITY ) ;
        $this->assertSame( 'type'    , AcceptField::TYPE    ) ;
    }

    public function testEnumsListsAllValues() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'params' , 'quality' , 'type' ] ,
            AcceptField::enums() ,
        ) ;
    }
}
