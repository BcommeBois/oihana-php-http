<?php

namespace tests\oihana\http\helpers\cookies ;

use oihana\http\enums\CookieOption ;
use PHPUnit\Framework\TestCase ;

use function oihana\http\helpers\cookies\expireSetCookieHeader ;

/**
 * Unit coverage for {@see \oihana\http\helpers\cookies\expireSetCookieHeader()}.
 */
class ExpireSetCookieHeaderTest extends TestCase
{
    public function testProducesClearingHeaderWithEmptyValueAndZeroMaxAge() :void
    {
        $this->assertSame
        (
            'access_token=; Path=/; Max-Age=0; SameSite=Lax; HttpOnly' ,
            expireSetCookieHeader( 'access_token' ) ,
        ) ;
    }

    public function testForwardsOptionsToUnderlyingBuilder() :void
    {
        $header = expireSetCookieHeader
        (
            'refresh_token' ,
            [
                CookieOption::DOMAIN => 'api.example.com' ,
                CookieOption::SECURE => true              ,
            ] ,
        ) ;

        $this->assertSame
        (
            'refresh_token=; Path=/; Max-Age=0; SameSite=Lax; HttpOnly; Secure; Domain=api.example.com' ,
            $header ,
        ) ;
    }
}
