<?php namespace com\xqmsg\sdk\v2\services;

use com\xqmsg\sdk\v2\algorithms\AESAlgorithm;
use com\xqmsg\sdk\v2\algorithms\Algorithm;
use com\xqmsg\sdk\v2\algorithms\OTPv2Algorithm;
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
class Decrypt extends XQModule
{
    public const DATA = "data";
    public const TOKEN = "token";
    public const ALGORITHM = 'algorithm';

    public const REQUIRED = array(self::DATA, self::TOKEN );

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
        return "decrypt";
    }

    /**
     * @param string $data
     * @param string $token
     * @param Algorithm|null $algorithm
     * @param string $authorization
     * @return ServerResponse
     * @throws StatusCodeException|JsonException
     */
    public function runWith(string $data, string $token, ?Algorithm $algorithm = null, string $authorization = ''): ServerResponse
    {
        return $this->run([
            self::ALGORITHM => $algorithm,
            self::DATA => $data,
            self::TOKEN => $token,
            AddKey::AUTHORIZATION => $authorization,
        ]);
    }

    /**
     * @inheritDoc
     * @noinspection DegradedSwitchInspection
     */
    public function run(array $args): ServerResponse
    {

        $this->validateInput($args, self::REQUIRED);

        $response = RetrieveKey::with($this->sdk())->runWith($args[self::TOKEN]);
        if (!$response->succeeded()) {
            return $response;
        }

        $key = $response->raw();
        if ($key[0] === '.') {
            switch ($key[1]) {
                case AESAlgorithm::PREFIX :
                    $algorithm = new AESAlgorithm();
                    break;
                default:
                    $algorithm = new OTPv2Algorithm();
            }
            $key = substr($key, 2);
        }
        else {
            $classname = Config::DEFAULT_ALGORITHM;
            $algorithm = new $classname();
        }

        $decryptedContent = $algorithm->decrypt( $key, $args[self::DATA] );

        return ServerResponse::ok( $decryptedContent );
    }
}