<?php

namespace tests\oihana\http\helpers\url ;

use PHPUnit\Framework\TestCase ;

use oihana\enums\http\UrlComponent ;

use function oihana\http\helpers\url\withUrlComponents ;

/**
 * Unit coverage for {@see \oihana\http\helpers\url\withUrlComponents()}.
 */
class WithUrlComponentsTest extends TestCase
{
    public function testSwitchSchemeOnly() :void
    {
        $this->assertSame
        (
            'https://example.com/path' ,
            withUrlComponents( 'http://example.com/path' , [ UrlComponent::SCHEME => 'https' ] )
        ) ;
    }

    public function testReplacePasswordOnly() :void
    {
        $this->assertSame
        (
            'https://user:new@example.com' ,
            withUrlComponents( 'https://user:old@example.com' , [ UrlComponent::PASS => 'new' ] )
        ) ;
    }

    public function testReplaceHostAndPort() :void
    {
        $this->assertSame
        (
            'https://other.example.org:9000/p?x=1' ,
            withUrlComponents
            (
                'https://example.com:8443/p?x=1' ,
                [ UrlComponent::HOST => 'other.example.org' , UrlComponent::PORT => 9000 ]
            )
        ) ;
    }

    public function testRemoveQueryAndFragment() :void
    {
        $this->assertSame
        (
            'https://example.com/p' ,
            withUrlComponents
            (
                'https://example.com/p?x=1#frag' ,
                [ UrlComponent::QUERY => null , UrlComponent::FRAGMENT => null ]
            )
        ) ;
    }

    public function testRemovePasswordKeepsUser() :void
    {
        $this->assertSame
        (
            'https://user@example.com' ,
            withUrlComponents( 'https://user:secret@example.com' , [ UrlComponent::PASS => null ] )
        ) ;
    }

    public function testRemoveHostDropsAuthorityBoundParts() :void
    {
        // Dropping the host removes the whole authority (user / pass / port);
        // the scheme and path are kept, yielding a scheme-only-path URI.
        $this->assertSame
        (
            'https:/path' ,
            withUrlComponents( 'https://user:p@example.com:8443/path' , [ UrlComponent::HOST => null ] )
        ) ;
    }

    public function testAddQueryToUrlWithoutQuery() :void
    {
        $this->assertSame
        (
            'https://example.com/p?a=1' ,
            withUrlComponents( 'https://example.com/p' , [ UrlComponent::QUERY => 'a=1' ] )
        ) ;
    }

    /**
     * Documents the "no percent-encoding" limit: values are inserted verbatim,
     * a space in the path is NOT encoded to %20.
     */
    public function testValuesAreInsertedVerbatim() :void
    {
        $this->assertSame
        (
            'https://example.com/a b' ,
            withUrlComponents( 'https://example.com/x' , [ UrlComponent::PATH => '/a b' ] )
        ) ;
    }

    public function testSetIPv6HostMustBeBracketed() :void
    {
        $this->assertSame
        (
            'http://[::1]:8080/p' ,
            withUrlComponents( 'http://example.com:8080/p' , [ UrlComponent::HOST => '[::1]' ] )
        ) ;
    }

    public function testFailOpenOnUnparseableUrl() :void
    {
        // parse_url() returns false on this input → returned untouched.
        $this->assertSame
        (
            'http://:80' ,
            withUrlComponents( 'http://:80' , [ UrlComponent::SCHEME => 'https' ] )
        ) ;
    }

    public function testEmptyOverridesIsIdentity() :void
    {
        $this->assertSame
        (
            'https://example.com/p?x=1#f' ,
            withUrlComponents( 'https://example.com/p?x=1#f' , [] )
        ) ;
    }
}
