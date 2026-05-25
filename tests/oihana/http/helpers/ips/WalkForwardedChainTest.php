<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\walkForwardedChain;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\walkForwardedChain()}.
 */
class WalkForwardedChainTest extends TestCase
{
    public function testEmptyChainReturnsNull(): void
    {
        $this->assertNull( walkForwardedChain( [] , [ '10.0.0.0/8' ] ) ) ;
    }

    public function testReturnsRightmostNonTrustedEntry(): void
    {
        $this->assertSame
        (
            '8.8.8.8' ,
            walkForwardedChain
            (
                [ '8.8.8.8' , '10.0.0.99' , '10.0.0.5' ] ,
                [ '10.0.0.0/8' ]
            )
        ) ;
    }

    public function testFullyTrustedChainReturnsNull(): void
    {
        $this->assertNull
        (
            walkForwardedChain
            (
                [ '10.0.0.99' , '10.0.0.5' ] ,
                [ '10.0.0.0/8' ]
            )
        ) ;
    }

    public function testRightmostEntryReturnedWhenNoTrustedProxy(): void
    {
        // Empty trusted-proxy list → right-most is the first non-trusted.
        $this->assertSame
        (
            '10.0.0.5' ,
            walkForwardedChain
            (
                [ '8.8.8.8' , '10.0.0.99' , '10.0.0.5' ] ,
                []
            )
        ) ;
    }

    public function testInvalidEntriesAreSkipped(): void
    {
        // 'garbage' on the right is skipped; '10.0.0.5' is trusted; '8.8.8.8' returned.
        $this->assertSame
        (
            '8.8.8.8' ,
            walkForwardedChain
            (
                [ '8.8.8.8' , '10.0.0.5' , 'garbage' ] ,
                [ '10.0.0.0/8' ]
            )
        ) ;
    }

    public function testMixedIPv4AndIPv6(): void
    {
        $this->assertSame
        (
            '8.8.8.8' ,
            walkForwardedChain
            (
                [ '8.8.8.8' , '2001:db8::1' ] ,
                [ '2001:db8::/32' ]
            )
        ) ;
    }
}
