<?php

namespace tests\oihana\http\helpers\signatures ;

use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\signatures\signUrl ;
use function oihana\http\helpers\signatures\verifySignedUrl ;

/**
 * Unit coverage for {@see \oihana\http\helpers\signatures\verifySignedUrl()}.
 */
class VerifySignedUrlTest extends TestCase
{
    public function testValidSignatureVerifies() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' , 600 ) ;
        $this->assertTrue( verifySignedUrl( $signed , 'secret' ) ) ;
    }

    public function testWrongSecretFails() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' , 600 ) ;
        $this->assertFalse( verifySignedUrl( $signed , 'wrong-secret' ) ) ;
    }

    public function testWrongAlgorithmFails() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' , null , 'sha256' ) ;
        $this->assertFalse( verifySignedUrl( $signed , 'secret' , 'sha512' ) ) ;
    }

    public function testMissingSigFails() :void
    {
        $this->assertFalse
        (
            verifySignedUrl( 'https://api.example.com/file?a=1' , 'secret' )
        ) ;
    }

    public function testEmptySigFails() :void
    {
        $this->assertFalse
        (
            verifySignedUrl( 'https://api.example.com/file?sig=' , 'secret' )
        ) ;
    }

    public function testEmptySecretFails() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' , 600 ) ;
        $this->assertFalse( verifySignedUrl( $signed , '' ) ) ;
    }

    public function testExpiredUrlFails() :void
    {
        // exp = 1 (year 1970) — already in the past.
        $url = 'https://api.example.com/file?exp=1&sig=garbage' ;
        $this->assertFalse( verifySignedUrl( $url , 'secret' ) ) ;
    }

    public function testFreshUrlPassesExpCheck() :void
    {
        // Sign with a generous TTL and verify immediately.
        $signed = signUrl( 'https://api.example.com/file' , 'secret' , 3600 ) ;
        $this->assertTrue( verifySignedUrl( $signed , 'secret' ) ) ;
    }

    public function testTamperedQueryFails() :void
    {
        $signed = signUrl( 'https://api.example.com/file?download=1' , 'secret' , 600 ) ;

        // Tamper with the query AFTER signing.
        $tampered = str_replace( 'download=1' , 'download=0' , $signed ) ;

        $this->assertFalse( verifySignedUrl( $tampered , 'secret' ) ) ;
    }

    public function testTamperedPathFails() :void
    {
        $signed = signUrl( 'https://api.example.com/file/42' , 'secret' , 600 ) ;
        $tampered = str_replace( '/file/42' , '/file/99' , $signed ) ;

        $this->assertFalse( verifySignedUrl( $tampered , 'secret' ) ) ;
    }

    public function testNonNumericExpRejected() :void
    {
        $url = 'https://api.example.com/file?exp=notanumber&sig=anything' ;
        $this->assertFalse( verifySignedUrl( $url , 'secret' ) ) ;
    }

    public function testMalformedBase64UrlSigRejected() :void
    {
        $url = 'https://api.example.com/file?sig=not!valid!base64url' ;
        $this->assertFalse( verifySignedUrl( $url , 'secret' ) ) ;
    }

    public function testUnparseableUrlReturnsFalse() :void
    {
        // parse_url() returns false on a malformed authority (empty host
        // with a port) → verifySignedUrl bails out early.
        $this->assertFalse( verifySignedUrl( 'http://:80' , 'secret' ) ) ;
    }

    public function testUnknownAlgorithmReturnsFalse() :void
    {
        $signed = signUrl( 'https://api.example.com/file' , 'secret' , 600 ) ;
        $this->assertFalse( verifySignedUrl( $signed , 'secret' , 'not-an-algo' ) ) ;
    }
}
