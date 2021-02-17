<?php namespace com\xqmsg\sdk\v2\services;

use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class GetUserSettings
 * @package com\xqmsg\sdk\v2\services
 */
class GetUserSettings extends XQModule {

    public const AUTHORIZATION = 'authorization';

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
        return "settings";
    }

    /**
     * @inheritDoc
     */
    public function run( array $args = null ): ServerResponse
    {
        $cache = $this->sdk()->getCache();

        // Ensure that there is an active profile and access token.
        $activeProfile = $cache->getActiveProfile(true);
        $authorization = $cache->getXQAccess($activeProfile, true);

         return $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), [],
            '',
            CallMethod::Get,
            Config::ApiKey(), $authorization , $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );
    }
}