<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\AuthorizationField ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\AuthorizationField}.
 */
class AuthorizationFieldTest extends TestCase
{
    public function testConstantsExposeLowercaseKeys() :void
    {
        $this->assertSame( 'credentials' , AuthorizationField::CREDENTIALS ) ;
        $this->assertSame( 'scheme'      , AuthorizationField::SCHEME      ) ;
    }

    public function testEnumsListsAllValues() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'credentials' , 'scheme' ] ,
            AuthorizationField::enums() ,
        ) ;
    }
}
