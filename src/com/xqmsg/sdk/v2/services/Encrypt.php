<?php namespace com\xqmsg\sdk\v2\services;

use com\xqmsg\sdk\v2\algorithms\Algorithm;
use Config;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;
use JsonException;

/**
 * Class ValidateAccessRequest
 * @package com\xqmsg\sdk\v2\services
 *
 */
class Encrypt extends XQModule
{
    public const  DATA = "data";

    public const ALGORITHM = "algorithm";

    public const REQUIRED = array(self::DATA, self::ALGORITHM, AddKey::RECIPIENTS, AddKey::EXPIRES_HOURS, AddKey::TYPE);

    /**
     * @param XQSDK $sdk
     * @return static
     */
    public static function with(XQSDK $sdk): self
    {
        return new self($sdk);
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return "encrypt";
    }

    /**
     * @param string $data
     * @param string $recipients
     * @param Algorithm|null $algorithm
     * @param int $expiresIn
     * @param string $type
     * @param array $meta
     * @param string $authorization
     * @return ServerResponse
     * @throws StatusCodeException | JsonException
     */
    public function runWith(string $data, string $recipients, ?Algorithm $algorithm = null, int $expiresIn = 24, array $meta = [], string $type = AddKey::TYPE_EMAIL, string $authorization = ''): ServerResponse
    {
        return $this->run([
            self::ALGORITHM => $algorithm,
            self::DATA => $data,
            AddKey::RECIPIENTS => $recipients,
            AddKey::EXPIRES_HOURS => $expiresIn,
            AddKey::TYPE => $type,
            AddKey::META => $meta,
            AddKey::AUTHORIZATION => $authorization,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function run(array $args): ServerResponse
    {

        $this->validateInput($args, self::REQUIRED);

        //1. Get a new quantum key.
        $response = GetQuantumEntropy::with($this->sdk())->run();
        if (!$response->succeeded()) {
            return $response;
        }

        $secret = GetQuantumEntropy::extend( $response->raw() , strlen($args[self::DATA] ) );

        //2. Encrypt the data using the provided algorithm.
        if (!isset($args[self::ALGORITHM])) {
            $classname = Config::DEFAULT_ALGORITHM;
            $algorithm = new $classname();
        }
        else if (is_a($args[self::ALGORITHM], Algorithm::class ) ) {
            $algorithm = $args[self::ALGORITHM];

        }
        else {
            throw new StatusCodeException("Invalid algorithm provided" );
        }

        $encryptedContent = $algorithm->encrypt( $secret,  $args[self::DATA] );

        //3. Submit the encryption key and get a token in return.
        $prefix = $algorithm->prefix();
        $args[AddKey::KEY] = ($prefix === '') ? $secret : '.'.$prefix.$secret;

        $response = AddKey::with($this->sdk())->run($args);
        if (!$response->succeeded()) {
            return ServerResponse::error( $response->status() );
        }

        if ($response->succeeded()) {
           return ServerResponse::ok(
               [
                   "token" => $response->raw(),
                   "secret" => $secret,
                   "data" => $encryptedContent
               ]);
        }

        return $response;

    }

}