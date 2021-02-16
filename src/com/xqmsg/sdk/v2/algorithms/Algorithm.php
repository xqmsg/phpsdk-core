<?php namespace com\xqmsg\sdk\v2\algorithms;

use com\xqmsg\sdk\v2\exceptions\StatusCodeException;

/**
 * Interface Algorithm
 * @package com\xqmsg\sdk\v2\algorithms
 */
interface Algorithm {

    /**
     * Get the key prefix used to identify the algorithm type.
     *
     * @return string
     */
    public function prefix() : string;

    /**
     * Encrypts the provided data using the provided key.
     *
     * @param string $key
     * @param string $data
     * @return string
     * @throws StatusCodeException
     */
    public function encrypt(string $key, string $data) : string;

    /**
     * Decrypts the provided data using the provided key.
     *
     * @param string $key
     * @param string $data
     * @return string
     * @throws StatusCodeException
     */
    public function decrypt(string $key, string $data) : string;

    /**
     * @param array $keyData
     * @param string $source_url
     * @param $output_handle
     * @return string|null
     * @throws StatusCodeException
     */
    public function encryptFile(array $keyData, string $source_url, $output_handle ) : string;

    /**
     * @param array $keyData
     * @param $source_handle
     * @param $output_handle
     * @return string|null
     * @throws StatusCodeException
     */
    public function decryptFile( array $keyData, $source_handle, $output_handle ) : string;

}