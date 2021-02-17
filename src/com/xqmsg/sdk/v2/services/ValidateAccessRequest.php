<?php namespace com\xqmsg\sdk\v2\services;

use com\xqmsg\sdk\v2\util\StatusCodes;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class ValidateAccessRequest
 * @package com\xqmsg\sdk\v2\services
 *
 * Validates the access request of the currently active profile.
 */
class ValidateAccessRequest extends XQModule {

    public const PIN = 'pin';
    public const REQUIRED = array( self::PIN );

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
        return "codevalidation";
    }

    /**
     * @inheritDoc
     */
    public function run( array $args = [ self::PIN => '' ] ): ServerResponse
    {
        $cache = $this->sdk()->getCache();

        // Ensure that there is an active profile.
        $activeProfile = $cache->getActiveProfile( true );

        if ( !($preauthToken = $cache->getXQPreauth( $activeProfile ))) {
            throw new StatusCodeException(
                "No preauthorization token found for " . $activeProfile,
                StatusCodes::HTTP_UNAUTHORIZED
            );
        }

        $response = $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), $args,
            '',
            CallMethod::Get,
            Config::ApiKey(), $preauthToken, $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

        if ($response->succeeded()) {
            return Exchange::with($this->sdk())->run([ Exchange::REQUEST => Exchange::FOR_DEFAULT ] );
        }

        return $response;

    }
}