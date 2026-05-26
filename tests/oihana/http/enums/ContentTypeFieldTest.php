<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\ContentTypeField ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\ContentTypeField}.
 */
class ContentTypeFieldTest extends TestCase
{
    public function testConstantsExposeLowercaseKeys() :void
    {
        $this->assertSame( 'boundary' , ContentTypeField::BOUNDARY ) ;
        $this->assertSame( 'charset'  , ContentTypeField::CHARSET  ) ;
        $this->assertSame( 'params'   , ContentTypeField::PARAMS   ) ;
        $this->assertSame( 'type'     , ContentTypeField::TYPE     ) ;
    }

    public function testEnumsListsAllValues() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'boundary' , 'charset' , 'params' , 'type' ] ,
            ContentTypeField::enums() ,
        ) ;
    }
}
