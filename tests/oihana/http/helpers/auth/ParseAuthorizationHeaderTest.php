<?php

namespace tests\oihana\http\helpers\auth ;

use oihana\enums\http\AuthScheme ;
use oihana\http\enums\AuthorizationField ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\auth\parseAuthorizationHeader ;

/**
 * Unit coverage for {@see \oihana\http\helpers\auth\parseAuthorizationHeader()}.
 */
class ParseAuthorizationHeaderTest extends TestCase
{
    public function testEmptyReturnsNull() :void
    {
        $this->assertNull( parseAuthorizationHeader( '' ) ) ;
    }

    public function testWhitespaceOnlyReturnsNull() :void
    {
        $this->assertNull( parseAuthorizationHeader( "  \t" ) ) ;
    }

    public function testBearer() :void
    {
        $this->assertSame
        (
            [
                AuthorizationField::SCHEME      => AuthScheme::BEARER ,
                AuthorizationField::CREDENTIALS => 'eyJhbGci.eyJzdWIi.signed' ,
            ] ,
            parseAuthorizationHeader( 'Bearer eyJhbGci.eyJzdWIi.signed' ) ,
        ) ;
    }

    public function testBasicWithBase64Credentials() :void
    {
        $this->assertSame
        (
            [
                AuthorizationField::SCHEME      => AuthScheme::BASIC ,
                AuthorizationField::CREDENTIALS => 'dXNlcjpwYXNz' ,
            ] ,
            parseAuthorizationHeader( 'Basic dXNlcjpwYXNz' ) ,
        ) ;
    }

    public function testSchemeCasingIsNormalisedToCanonical() :void
    {
        // BEARER, bearer, BeArEr — all normalise to 'Bearer'.
        foreach ( [ 'BEARER' , 'bearer' , 'BeArEr' ] as $variant )
        {
            $parsed = parseAuthorizationHeader( $variant . ' tok' ) ;
            $this->assertSame
            (
                AuthScheme::BEARER ,
                $parsed[ AuthorizationField::SCHEME ] ,
                "Failed normalising scheme variant '$variant'" ,
            ) ;
        }
    }

    public function testUnknownSchemeIsPreservedAsIs() :void
    {
        $parsed = parseAuthorizationHeader( 'MyCustomScheme abc=def' ) ;

        $this->assertSame( 'MyCustomScheme' , $parsed[ AuthorizationField::SCHEME ] ) ;
        $this->assertSame( 'abc=def'        , $parsed[ AuthorizationField::CREDENTIALS ] ) ;
    }

    public function testSchemeOnlyHasEmptyCredentials() :void
    {
        $this->assertSame
        (
            [
                AuthorizationField::SCHEME      => AuthScheme::BEARER ,
                AuthorizationField::CREDENTIALS => '' ,
            ] ,
            parseAuthorizationHeader( 'Bearer' ) ,
        ) ;
    }

    public function testSurroundingWhitespaceIsTrimmed() :void
    {
        $parsed = parseAuthorizationHeader( "   Bearer tok  \n" ) ;

        $this->assertSame( AuthScheme::BEARER , $parsed[ AuthorizationField::SCHEME ] ) ;
        $this->assertSame( 'tok'              , $parsed[ AuthorizationField::CREDENTIALS ] ) ;
    }

    public function testCredentialsWithEmbeddedCommasArePreserved() :void
    {
        // Digest credentials carry multiple comma-separated parameters.
        $parsed = parseAuthorizationHeader
        (
            'Digest username="alice", realm="api", nonce="42", response="abc"'
        ) ;

        $this->assertSame( AuthScheme::DIGEST , $parsed[ AuthorizationField::SCHEME ] ) ;
        $this->assertSame
        (
            'username="alice", realm="api", nonce="42", response="abc"' ,
            $parsed[ AuthorizationField::CREDENTIALS ] ,
        ) ;
    }
}
