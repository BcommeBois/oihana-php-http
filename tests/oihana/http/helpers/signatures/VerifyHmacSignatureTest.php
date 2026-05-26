<?php

namespace tests\oihana\http\helpers\signatures ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\signatures\verifyHmacSignature ;

/**
 * Unit coverage for {@see \oihana\http\helpers\signatures\verifyHmacSignature()}.
 */
class VerifyHmacSignatureTest extends TestCase
{
    private const string SECRET = 'shared-secret' ;
    private const string PAYLOAD = '{"event":"checkout.session.completed","id":"42"}' ;

    public function testHexSignatureValid() :void
    {
        $sig = hash_hmac( 'sha256' , self::PAYLOAD , self::SECRET ) ;

        $this->assertTrue
        (
            verifyHmacSignature( self::PAYLOAD , $sig , self::SECRET )
        ) ;
    }

    public function testHexSignatureInvalid() :void
    {
        $this->assertFalse
        (
            verifyHmacSignature( self::PAYLOAD , 'deadbeef' , self::SECRET )
        ) ;
    }

    public function testTamperedPayloadFails() :void
    {
        $sig = hash_hmac( 'sha256' , self::PAYLOAD , self::SECRET ) ;

        $this->assertFalse
        (
            verifyHmacSignature( self::PAYLOAD . 'X' , $sig , self::SECRET )
        ) ;
    }

    public function testWrongSecretFails() :void
    {
        $sig = hash_hmac( 'sha256' , self::PAYLOAD , self::SECRET ) ;

        $this->assertFalse
        (
            verifyHmacSignature( self::PAYLOAD , $sig , 'wrong' )
        ) ;
    }

    public function testBase64FormatValid() :void
    {
        $raw = hash_hmac( 'sha256' , self::PAYLOAD , self::SECRET , true ) ;
        $sig = base64_encode( $raw ) ;

        $this->assertTrue
        (
            verifyHmacSignature( self::PAYLOAD , $sig , self::SECRET , 'sha256' , 'base64' )
        ) ;
    }

    public function testBase64UrlFormatValid() :void
    {
        $raw = hash_hmac( 'sha256' , self::PAYLOAD , self::SECRET , true ) ;
        $sig = rtrim( strtr( base64_encode( $raw ) , '+/' , '-_' ) , '=' ) ;

        $this->assertTrue
        (
            verifyHmacSignature( self::PAYLOAD , $sig , self::SECRET , 'sha256' , 'base64url' )
        ) ;
    }

    public function testFormatMismatchFails() :void
    {
        // Signature is hex but format says base64 → mismatch.
        $hexSig = hash_hmac( 'sha256' , self::PAYLOAD , self::SECRET ) ;

        $this->assertFalse
        (
            verifyHmacSignature( self::PAYLOAD , $hexSig , self::SECRET , 'sha256' , 'base64' )
        ) ;
    }

    public function testEmptySignatureFails() :void
    {
        $this->assertFalse
        (
            verifyHmacSignature( self::PAYLOAD , '' , self::SECRET )
        ) ;
    }

    public function testEmptySecretFails() :void
    {
        $this->assertFalse
        (
            verifyHmacSignature( self::PAYLOAD , 'anything' , '' )
        ) ;
    }

    public function testUnknownAlgorithmFails() :void
    {
        $this->assertFalse
        (
            verifyHmacSignature( self::PAYLOAD , 'sig' , self::SECRET , 'not-an-algo' )
        ) ;
    }

    public function testUnknownFormatFails() :void
    {
        $sig = hash_hmac( 'sha256' , self::PAYLOAD , self::SECRET ) ;

        $this->assertFalse
        (
            verifyHmacSignature( self::PAYLOAD , $sig , self::SECRET , 'sha256' , 'banana' )
        ) ;
    }

    public function testSha512Algorithm() :void
    {
        $sig = hash_hmac( 'sha512' , self::PAYLOAD , self::SECRET ) ;

        $this->assertTrue
        (
            verifyHmacSignature( self::PAYLOAD , $sig , self::SECRET , 'sha512' )
        ) ;
    }

    public function testEmptyPayloadIsValidInput() :void
    {
        $sig = hash_hmac( 'sha256' , '' , self::SECRET ) ;

        $this->assertTrue
        (
            verifyHmacSignature( '' , $sig , self::SECRET )
        ) ;
    }
}
