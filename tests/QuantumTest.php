<?php

require_once __DIR__ . "/../src/AutoLoader.php";

use com\xqmsg\sdk\v2\services\GetQuantumEntropy;
use PHPUnit\Framework\TestCase;
use com\xqmsg\sdk\v2\XQSDK;

class QuantumTest extends TestCase
{
    private const KEY_LENGTH = 32;

    public function testFetchQuantum(): void
    {
        // Attempt to fetch a quantum key.
        try {
            $sdk = new XQSDK();
            $result = GetQuantumEntropy::with($sdk)->runWith(self::KEY_LENGTH,false);
            self::assertTrue(   $result->succeeded(), $result->status() );
            $entropy = GetQuantumEntropy::extend($result->raw(), self::KEY_LENGTH);
            self::assertEquals( self::KEY_LENGTH,  strlen($entropy) );
        } catch (Exception $e) {
            self::fail($e->getMessage());
        }
    }
}