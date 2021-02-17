<?php namespace com\xqmsg\sdk\v2\services;

use JsonException;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class UpdateUserSettings
 * @package com\xqmsg\sdk\v2\services
 */
class UpdateUserSettings extends XQModule {

    public const NEWSLETTERS = 'newsletter';
    public const NOTIFICATIONS = 'notifications';
    public const AUTHORIZATION = 'authorization';
    public const REQUIRED = array( self::NEWSLETTERS,self::NOTIFICATIONS );

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
     * @param bool $receiveNewsletters
     * @param int $notificationLevel
     * @return ServerResponse
     * @throws StatusCodeException | JsonException
     */
    public function runWith( bool $receiveNewsletters, int $notificationLevel ) : ServerResponse {
        return $this->run( [self::NEWSLETTERS => $receiveNewsletters , self::NOTIFICATIONS => $notificationLevel ] );
    }

    /**
     * @inheritDoc
     */
    public function run( array $args ): ServerResponse
    {
        $this->validateInput( $args, self::REQUIRED );
        $cache = $this->sdk()->getCache();
        $activeProfile = $cache->getActiveProfile( true );
        $authorization = $cache->getXQAccess( $activeProfile, true );

        return $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), [],
            json_encode($args, JSON_THROW_ON_ERROR),
            CallMethod::Patch,
            Config::ApiKey(), $authorization , $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );
    }
}