<?php namespace com\xqmsg\sdk\v2\services;


use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class RequestAccess
 * @package com\xqmsg\sdk\v2\services
 */
class RequestAccess extends XQModule {

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
       return "authorize";
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
            Config::SubscriptionKey(), '', $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

        if ($response->succeeded()) {
            $cache = $this->sdk()->getCache();
            $exchangeToken = $response->raw();
            if ($exchangeToken[0] === '{' ) {
                $json = $response->json();
                $cache->addXQPreauth( $args[self::USER], $json->exchange );
            }
            else {
                $cache->addXQPreauth( $args[self::USER], $exchangeToken );
            }

            $cache->setActiveProfile($args[self::USER]);
            return $response;
        }

        return $response;

    }
}