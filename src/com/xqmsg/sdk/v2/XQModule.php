<?php namespace com\xqmsg\sdk\v2;


use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use com\xqmsg\sdk\v2\util\StatusCodes;
use JsonException;

/**
 * Class XQModule
 * @package com\xqmsg\sdk\v2
 */
abstract class XQModule
{
    private XQSDK $sdk;

    /**
     * XQModule constructor.
     * @param XQSDK $sdk
     */
    public function __construct(XQSDK $sdk)
    {
        $this->sdk = $sdk;
    }

    /**
     * @return XQSDK
     */
    public function sdk() : XQSDK {
        return $this->sdk;
    }

    /**
     * @param array $maybeArgs
     * @param array $requiredFields
     * @return array
     * @throws StatusCodeException
     */
    public function validateInput(array $maybeArgs, array $requiredFields): array
    {

        if (empty($requiredFields)) {
            return $maybeArgs;
        }

        if (!$maybeArgs) {
            throw new StatusCodeException(
                "Missing: " . $requiredFields,
                StatusCodes::HTTP_BAD_REQUEST
            ) ;
        }

        $missing = array_filter( $requiredFields, static function($item) use ($maybeArgs) {
            return array_key_exists($item, $maybeArgs);
        });

        if (empty($missing)) {
            throw new StatusCodeException(
                "Missing: [" . $missing . "]",
                StatusCodes::HTTP_BAD_REQUEST
            );
        }
        return $maybeArgs;
    }

    /**
     * @param array $args
     * @return ServerResponse
     * @throws StatusCodeException | JsonException
     */
    abstract public function run(array $args) : ServerResponse;

    /**
     * @return string
     */
    abstract public function name() : string;
}