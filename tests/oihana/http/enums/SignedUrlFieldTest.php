<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\SignedUrlField ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\SignedUrlField}.
 */
class SignedUrlFieldTest extends TestCase
{
    public function testConstantsExposeWireNames() :void
    {
        $this->assertSame( 'sig' , SignedUrlField::SIGNATURE ) ;
        $this->assertSame( 'exp' , SignedUrlField::EXPIRY    ) ;
    }

    public function testIncludesAcceptsKnownValues() :void
    {
        $this->assertTrue( SignedUrlField::includes( SignedUrlField::SIGNATURE ) ) ;
        $this->assertTrue( SignedUrlField::includes( SignedUrlField::EXPIRY    ) ) ;
    }

    public function testIncludesRejectsUnknownValues() :void
    {
        $this->assertFalse( SignedUrlField::includes( 'signature' ) ) ;
        $this->assertFalse( SignedUrlField::includes( 'SIG'       ) ) ; // case-sensitive
        $this->assertFalse( SignedUrlField::includes( ''          ) ) ;
    }

    public function testEnumsListsBothNames() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'sig' , 'exp' ] ,
            SignedUrlField::enums() ,
        ) ;
    }

    public function testGetConstantKeysExposesNames() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'SIGNATURE' , 'EXPIRY' ] ,
            SignedUrlField::getConstantKeys() ,
        ) ;
    }
}
