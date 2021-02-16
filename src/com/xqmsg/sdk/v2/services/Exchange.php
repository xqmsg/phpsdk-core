<?php namespace com\xqmsg\sdk\v2\services;


use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;

/**
 * Class Exchange
 * @package com\xqmsg\sdk\v2\services
 */
class Exchange extends XQModule {

    public const FOR_DEFAULT = "xq";
    public const FOR_DASHBOARD = "dashboard";
    public const REQUEST = 'request';
    public const REQUIRED = array( self::REQUEST );

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
        return "exchange";
    }

    /**
     * @inheritDoc
     */
    public function run( array $args = [ self::REQUEST => self::FOR_DEFAULT ] ): ServerResponse
    {

        $this->validateInput( $args, self::REQUIRED );

        $cache = $this->sdk()->getCache();

        // Ensure that there is an active profile.
        $activeProfile = $cache->getActiveProfile( true );

        if ( $args[self::REQUEST] === self::FOR_DASHBOARD ) {
            $authorization = $cache->getXQAccess($activeProfile);
        }
        else {
            $authorization = $cache->getXQPreauth($activeProfile);
        }

        if (!$authorization || empty($authorization)) {
            throw new StatusCodeException("No authorization token available" );
        }

        $response = $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), $args,
            '',
            CallMethod::Get,
            Config::SubscriptionKey(), $authorization , $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

        if ($response->succeeded()) {

            if ( $args[self::REQUEST] === self::FOR_DASHBOARD ) {
               $cache->addDashboardAccess( $activeProfile,  $response->raw() );
            }
            else {
                $cache->addXQAccess( $activeProfile, $response->raw());
                $cache->clearXQPreauth($activeProfile);
            }
        }

        return $response;

    }
}