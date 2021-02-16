<?php namespace com\xqmsg\sdk\v2\exceptions;

use com\xqmsg\sdk\v2\util\StatusCodes;
use Exception;

/**
 * Class StatusCodeException
 * @package com\xqmsg\sdk\v2\exceptions
 */
class StatusCodeException extends Exception {
    public static function missing() : StatusCodeException{
        return new self(StatusCodes::HTTP_INTERNAL_SERVER_ERROR, "Not Implemented" );
    }
}