<?php

namespace tests\oihana\http\helpers\signatures ;

use InvalidArgumentException ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\signatures\signUrl ;
use function oihana\http\helpers\signatures\verifySignedUrl ;

/**
 * Unit coverage for {@see \oihana\http\helpers\signatures\signUrl()}.
 */
class SignUrlTest extends TestCase
{
    public function testProducesSigQueryParameter() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' ) ;

        $this->assertStringContainsString( 'sig=' , $signed ) ;
    }

    public function testTtlAddsExpParameter() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' , 600 ) ;

        $this->assertStringContainsString( 'exp=' , $signed ) ;
    }

    public function testNullTtlDoesNotAddExpParameter() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' , null ) ;

        $this->assertStringNotContainsString( 'exp=' , $signed ) ;
    }

    public function testRoundtripsCleanlyWithVerifySignedUrl() :void
    {
        $signed = signUrl( 'https://api.example.com/file?download=1' , 's3cret' , 600 ) ;
        $this->assertTrue( verifySignedUrl( $signed , 's3cret' ) ) ;
    }

    public function testIdempotentReSigning() :void
    {
        $first  = signUrl( 'https://api.example.com/file' , 'secret' ) ;
        $second = signUrl( $first , 'secret' ) ;

        // Re-signing strips and re-emits sig — both URLs must verify.
        $this->assertTrue( verifySignedUrl( $first  , 'secret' ) ) ;
        $this->assertTrue( verifySignedUrl( $second , 'secret' ) ) ;
    }

    public function testQueryParameterOrderDoesNotAffectSignature() :void
    {
        // Both URLs should produce the SAME signature thanks to
        // normalizeUrl()'s alphabetical sort.
        $a = signUrl( 'https://api.example.com/x?a=1&b=2' , 'secret' ) ;
        $b = signUrl( 'https://api.example.com/x?b=2&a=1' , 'secret' ) ;

        $this->assertSame( $a , $b ) ;
    }

    public function testEmptySecretThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        signUrl( 'https://api.example.com/file' , '' ) ;
    }

    public function testUnknownAlgorithmThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        signUrl( 'https://api.example.com/file' , 'secret' , null , 'not-an-algo' ) ;
    }

    public function testUnparseableUrlThrows() :void
    {
        $this->expectException( InvalidArgumentException::class ) ;
        signUrl( 'http:///' , 'secret' ) ;  // parse_url rejects this
    }

    public function testHashAlgorithmChangesSignature() :void
    {
        $sha256 = signUrl( 'https://api.example.com/file' , 'secret' , null , 'sha256' ) ;
        $sha512 = signUrl( 'https://api.example.com/file' , 'secret' , null , 'sha512' ) ;

        $this->assertNotSame( $sha256 , $sha512 ) ;
    }

    public function testSignatureIsBase64UrlWithoutPadding() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' ) ;

        preg_match( '/sig=([^&]+)/' , $signed , $m ) ;
        $sig = $m[ 1 ] ;

        $this->assertStringNotContainsString( '+' , $sig ) ;
        $this->assertStringNotContainsString( '/' , $sig ) ;
        $this->assertStringNotContainsString( '=' , $sig ) ;
    }
}
