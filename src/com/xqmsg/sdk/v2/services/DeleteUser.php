<?php namespace com\xqmsg\sdk\v2\services;

use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class DeleteUser
 * @package com\xqmsg\sdk\v2\services
 */
class DeleteUser extends XQModule {

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
        return "subscriber";
    }

    /**
     * @inheritDoc
     */
    public function run( array $args = null ): ServerResponse
    {
        $cache = $this->sdk()->getCache();
        $activeProfile = $cache->getActiveProfile( true );
        $authorization = $cache->getXQAccess( $activeProfile, true );

        $response = $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), [],
            '',
            CallMethod::Delete,
            Config::ApiKey(), $authorization , $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

        if ($response->succeeded()) {
            $cache->clearProfile( $activeProfile );
            return $response;
        }
        return $response;
    }
}