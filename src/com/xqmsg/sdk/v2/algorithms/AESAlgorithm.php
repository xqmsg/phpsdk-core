<?php namespace com\xqmsg\sdk\v2\algorithms;

use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use com\xqmsg\sdk\v2\util\CryptoAES;

/**
 * Implementation of the AES algorithm using CryptoAES
 *
 * Class AESAlgorithm
 * @package xq\sdk\algorithms
 *
 */
class AESAlgorithm implements Algorithm {

    public const PREFIX = 'A';

    /**
     * @inheritDoc
     */
    public function prefix(): string
    {
        return self::PREFIX;
    }

    /**
     * @inheritDoc
     */
    public function encrypt(string $key, string $data) : string
    {
        return CryptoAES::encrypt($data, $key);
    }

    /**
     * @inheritDoc
     */
    public function decrypt(string $key, string $data) : string
    {
        return CryptoAES::decrypt($data, $key);
    }

    /**
     * @inheritDoc
     */
    public function encryptFile(array $keyData, string $source_url, $output_handle): string
    {
        throw StatusCodeException::missing();
    }

    /**
     * @inheritDoc
     */
    public function decryptFile(array $keyData, $source_handle, $output_handle ) : string
    {
        throw StatusCodeException::missing();
    }
}