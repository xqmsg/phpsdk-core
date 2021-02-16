<?php namespace com\xqmsg\sdk\v2\services;

use JsonException;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class GrantKeyAccess
 * @package com\xqmsg\sdk\v2\services
 */
class GrantKeyAccess extends XQModule {

    public const TOKEN = 'token';
    public const RECIPIENTS = 'recipients';
    public const REQUIRED = array( self::TOKEN, self::RECIPIENTS );

    /**
     * @param XQSDK $sdk
     * @return static
     */
    public static function with(XQSDK $sdk) : self {
        return new self($sdk);
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return "grant";
    }

    /**
     * @param string $token
     * @param array $recipients
     * @return ServerResponse
     * @throws JsonException
     * @throws StatusCodeException
     */
    public function runWith(string $token, array $recipients) : ServerResponse {
        return $this->run([self::TOKEN => $token, self::RECIPIENTS => $recipients ] ) ;
    }

    /**
     * @inheritDoc
     */
    public function run( array $args = null ): ServerResponse
    {
        $this->validateInput( $args, self::REQUIRED );
        $cache = $this->sdk()->getCache();
        $activeProfile = $cache->getActiveProfile( true );
        $authorization = $cache->getXQAccess( $activeProfile, true );
        $encodedToken = rawurlencode($args[self::TOKEN]);
        $body = json_encode([
            self::RECIPIENTS => $args[self::RECIPIENTS]
        ], JSON_THROW_ON_ERROR );

        return $this->sdk()->call(
            Config::ValidationHost(),
            implode("/" , array($this->name() , $encodedToken)),
            [],
            $body,
            CallMethod::Post,
            Config::ValidationKey(), $authorization , $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

    }
}