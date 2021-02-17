<?php namespace com\xqmsg\sdk\v2\services;

use JsonException;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class RequestAliasAccess
 * @package com\xqmsg\sdk\v2\services
 */
class RequestAliasAccess extends XQModule {

    public const USER = 'user';
    public const REQUIRED = array( self::USER );


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
        return "authorizealias";
    }


    /**
     * @param string $user
     * @return ServerResponse
     * @throws JsonException
     * @throws StatusCodeException
     */
    public function runWith(string $user) : ServerResponse {
        return $this->run([self::USER => $user]);
    }

    /**
     * @inheritDoc
     */
    public function run( array $args ): ServerResponse
    {
        $this->validateInput( $args, self::REQUIRED );

        $response = $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), [],
            json_encode( $args, JSON_THROW_ON_ERROR ),
            CallMethod::Post,
            Config::ApiKey(), '', $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

        if ($response->succeeded()) {
            $cache = $this->sdk()->getCache();
            $authToken = $response->raw();
            $cache->addXQAccess( $args[self::USER], $authToken );
            $cache->setActiveProfile($args[self::USER]);
            return $response;
        }

        return $response;

    }
}