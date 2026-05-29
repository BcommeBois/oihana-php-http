<?php

namespace tests\oihana\http\enums ;

use oihana\http\enums\SignatureFormat ;
use oihana\reflect\exceptions\ConstantException ;
use PHPUnit\Framework\TestCase ;

/**
 * Unit coverage for {@see \oihana\http\enums\SignatureFormat}.
 */
class SignatureFormatTest extends TestCase
{
    public function testConstantsExposeWireValues() :void
    {
        $this->assertSame( 'hex'       , SignatureFormat::HEX       ) ;
        $this->assertSame( 'base64'    , SignatureFormat::BASE64    ) ;
        $this->assertSame( 'base64url' , SignatureFormat::BASE64URL ) ;
    }

    public function testIncludesAcceptsKnownValues() :void
    {
        $this->assertTrue( SignatureFormat::includes( SignatureFormat::HEX       ) ) ;
        $this->assertTrue( SignatureFormat::includes( SignatureFormat::BASE64    ) ) ;
        $this->assertTrue( SignatureFormat::includes( SignatureFormat::BASE64URL ) ) ;
    }

    public function testIncludesRejectsUnknownValues() :void
    {
        $this->assertFalse( SignatureFormat::includes( 'banana' ) ) ;
        $this->assertFalse( SignatureFormat::includes( 'HEX'    ) ) ; // case-sensitive
        $this->assertFalse( SignatureFormat::includes( ''       ) ) ;
    }

    public function testEnumsListsAllThreeValues() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'hex' , 'base64' , 'base64url' ] ,
            SignatureFormat::enums() ,
        ) ;
    }

    public function testGetConstantKeysExposesNames() :void
    {
        $this->assertEqualsCanonicalizing
        (
            [ 'HEX' , 'BASE64' , 'BASE64URL' ] ,
            SignatureFormat::getConstantKeys() ,
        ) ;
    }

    /**
     * @return void
     * @throws ConstantException
     */
    public function testValidateAcceptsKnownValue() :void
    {
        SignatureFormat::validate( SignatureFormat::HEX ) ;

        $this->expectNotToPerformAssertions() ;
    }

    public function testValidateThrowsOnUnknownValue() :void
    {
        $this->expectException( ConstantException::class ) ;

        SignatureFormat::validate( 'banana' ) ;
    }
}
