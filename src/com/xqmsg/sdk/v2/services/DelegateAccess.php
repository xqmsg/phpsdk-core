<?php namespace com\xqmsg\sdk\v2\services;

use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class ValidateAccessRequest
 * @package com\xqmsg\sdk\v2\services
 *
 */
class DelegateAccess extends XQModule {

    public static function with(XQSDK $sdk) : self {
        return new self($sdk);
    }

    /**
     * @inheritDoc
    */
    public function name(): string
    {
        return "delegate";
    }

    /**
     * @inheritDoc
     */
    public function run( array $args = null ): ServerResponse
    {
        $cache = $this->sdk()->getCache();

        // Ensure that there is an active profile and access token.
        $activeProfile = $cache->getActiveProfile( true );
        $authorization = $cache->getXQAccess( $activeProfile, true );

        return $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), [],
            json_encode($args, JSON_THROW_ON_ERROR),
            CallMethod::Get,
            Config::SubscriptionKey(), $authorization, $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );
    }
}