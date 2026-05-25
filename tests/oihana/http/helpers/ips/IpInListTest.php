<?php

namespace tests\oihana\http\helpers\ips;

use PHPUnit\Framework\TestCase;

use function oihana\http\helpers\ips\ipInList;

/**
 * Unit coverage for {@see \oihana\http\helpers\ips\ipInList()}.
 */
class IpInListTest extends TestCase
{
    public function testEmptyListAlwaysReturnsFalse(): void
    {
        $this->assertFalse( ipInList( '8.8.8.8'   , [] ) ) ;
        $this->assertFalse( ipInList( '127.0.0.1' , [] ) ) ;
    }

    public function testMatchesBareIp(): void
    {
        $this->assertTrue ( ipInList( '127.0.0.1' , [ '127.0.0.1' , '10.0.0.0/8' ] ) ) ;
        $this->assertFalse( ipInList( '127.0.0.2' , [ '127.0.0.1' ] ) ) ;
    }

    public function testMatchesCidrRange(): void
    {
        $this->assertTrue ( ipInList( '10.1.2.3' , [ '127.0.0.1' , '10.0.0.0/8' ] ) ) ;
        $this->assertFalse( ipInList( '8.8.8.8'  , [ '127.0.0.1' , '10.0.0.0/8' ] ) ) ;
    }

    public function testMatchesIPv6CidrRange(): void
    {
        $this->assertTrue ( ipInList( '2001:db8::1' , [ '2001:db8::/32' ] ) ) ;
        $this->assertFalse( ipInList( '2001:db9::1' , [ '2001:db8::/32' ] ) ) ;
    }

    public function testInvalidEntriesAreSkipped(): void
    {
        // 'garbage' fails ipMatchesCidr → continues to the next entry.
        $this->assertTrue( ipInList( '127.0.0.1' , [ 'garbage' , '127.0.0.1' ] ) ) ;
    }
}
