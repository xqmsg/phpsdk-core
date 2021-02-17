<?php /** @noinspection PhpUnused */

namespace com\xqmsg\sdk\v2\services;

use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class DeleteAccessCredentials
 * @package com\xqmsg\sdk\v2\services
 */
class DeleteAccessCredentials extends XQModule {

    public const AUTHORIZATION = 'authorization';

    public static function with(XQSDK $sdk) : self {
        return new self($sdk);
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return "authorization";
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

        $response = $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), [],
            '',
            CallMethod::Delete,
            Config::ApiKey(), $authorization , $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

        if ($response->succeeded()) {
            if ($activeProfile) $cache->clearProfile( $activeProfile );
            return $response;
        }
        return $response;
    }
}