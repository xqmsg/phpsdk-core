<?php namespace com\xqmsg\sdk\v2\services;

use JsonException;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class RevokeKey
 * @package com\xqmsg\sdk\v2\services
 */
class RevokeKey extends XQModule {

    public const TOKEN = 'token';
    public const AUTHORIZATION = 'authorization';
    public const REQUIRED = array( self::TOKEN );

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
        return "key";
    }

    /**
     * @param string $token
     * @return ServerResponse
     * @throws StatusCodeException | JsonException
     */
    public function runWith(string $token ) : ServerResponse {
        return $this->run([self::TOKEN => $token ] );
    }

    /**
     * @inheritDoc
     */
    public function run( array $args ): ServerResponse
    {
        $this->validateInput( $args, self::REQUIRED );
        $cache = $this->sdk()->getCache();

        if ( ($args[self::AUTHORIZATION] ?? '') !== '' ) {
            $authorization = $args[self::AUTHORIZATION];
        }
        else {
            $activeProfile = $cache->getActiveProfile( true );
            $authorization = $cache->getXQAccess( $activeProfile, true );
        }

        $encodedToken = rawurlencode($args[self::TOKEN]);

        return $this->sdk()->call(
            Config::ValidationHost(),
            implode("/" , array($this->name() , $encodedToken)),
            [],
            '',
            CallMethod::Delete,
            Config::ApiKey(), $authorization, $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );
    }
}