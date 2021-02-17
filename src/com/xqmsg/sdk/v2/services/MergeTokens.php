<?php namespace com\xqmsg\sdk\v2\services;

use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;
use JsonException;

/**
 * Class MergeTokens
 * @package com\xqmsg\sdk\v2\services
 */
class MergeTokens extends XQModule {

    public const TOKENS = 'tokens';
    public const REQUIRED = array( self::TOKENS );

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
        return "combined";
    }

    /**
     * @param array $tokens
     * @return ServerResponse
     * @throws StatusCodeException | JsonException
     */
    public function runWith(array $tokens ) : ServerResponse {
        return $this->run([self::TOKENS => $tokens ] );
    }

    /**
     * @inheritDoc
     */
    public function run( array $args = null ): ServerResponse
    {

        $cache = $this->sdk()->getCache();

        $activeProfile = $cache->getActiveProfile( true );
        $authorization = $cache->getXQAccess( $activeProfile, true );

        if ( !empty($args[self::TOKENS] ?? [] )  ) {
            $tokens = $args[self::TOKENS];
        }
        else {
            $profiles = $cache->listProfiles();
            $tokens = [];
            foreach( $profiles as $profile ) {
                if ( $accessToken = $cache->getXQAccess($profile) ) {
                    $tokens[] = $accessToken;
                }
            }
        }

        if (empty($tokens)) {
            return ServerResponse::error( 'No tokens available to merge.');
        }

        return $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(),
            [],
            json_encode([self::TOKENS => $tokens], JSON_THROW_ON_ERROR),
            CallMethod::Post,
            Config::ApiKey(), $authorization, $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );
    }
}