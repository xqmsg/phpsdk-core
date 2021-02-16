<?php namespace com\xqmsg\sdk\v2\util;

use com\xqmsg\sdk\v2\exceptions\StatusCodeException;

/**
 * Class CryptoAES
 * @package com\xqmsg\sdk\v2\util
 */
class CryptoAES
{

    /**
     * @param string $data The data to encrypt.
     * @param string $passphrase The encryption passphrase.
     * @param string|null $salt The encryption salt.
     * @return string The encrypted data in Base64 format.
     */
    public static function encrypt(string $data, string $passphrase, string $salt = null): string
    {

        /** @var  null */
        if (!$salt) {
            $strong_result = NULL;
            $salt = openssl_random_pseudo_bytes(8);
        }
        [$key, $iv] = self::evpkdf($passphrase, $salt);
        $ct = openssl_encrypt($data, 'aes-256-cbc', $key, true, $iv);
        return self::encode($ct, $salt);
    }

    /**
     * @param string $base64 encrypted data in base64 OpenSSL format
     * @param string $passphrase
     * @return string
     * @throws StatusCodeException
     */
    public static function decrypt(string $base64, string $passphrase): string
    {
        [$ct, $salt] = self::decode($base64);
        [$key, $iv] = self::evpkdf($passphrase, $salt);
        return openssl_decrypt($ct, 'aes-256-cbc', $key, true, $iv);
    }

    /**
     * @param string $passphrase
     * @param string $salt
     * @return array
     */
    public static function evpkdf(string $passphrase, string $salt): array
    {
        $salted = '';
        $dx = '';
        while (strlen($salted) < 48) {
            $dx = md5($dx . $passphrase . $salt, true);
            $salted .= $dx;
        }
        $key = substr($salted, 0, 32);
        $iv = substr($salted, 32, 16);

        return [$key, $iv];
    }


    /**
     * @param $base64
     * @return array
     * @throws StatusCodeException
     */
    public static function decode($base64): array
    {
        $data = base64_decode($base64);

        if (strpos($data, "Salted__") !== 0) {
            throw new StatusCodeException("Invalid hash salt" );
        }
        $salt = substr($data, 8, 8);
        $ct = substr($data, 16);
        return [$ct, $salt];
    }

    /**
     * @param $ct
     * @param $salt
     * @return string
     */
    public static function encode($ct, string $salt): string
    {
        return base64_encode("Salted__" . $salt . $ct);
    }
}