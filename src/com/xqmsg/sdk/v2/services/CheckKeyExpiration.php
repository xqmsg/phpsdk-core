<?php namespace com\xqmsg\sdk\v2\services;

use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;
use JsonException;

/**
 * Class CheckKeyExpiration
 * @package com\xqmsg\sdk\v2\services
 */
class CheckKeyExpiration extends XQModule {

    public const TOKEN = 'token';
    public const AUTHORIZATION = 'authorization';
    public const REQUIRED = array( self::TOKEN );

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
        return "expiration";
    }

    /**
     * @param string $token
     * @return ServerResponse
     * @throws StatusCodeException
     * @throws JsonException
     */
    public function runWith(string $token ) : ServerResponse {
        return $this->run([self::TOKEN => $token ] );
    }

    /**
     * @inheritDoc
     */
    public function run( array $args ): ServerResponse
    {
        $cache = $this->sdk()->getCache();

        // Ensure that there is an active profile and access token.
        if (($args[self::AUTHORIZATION] ?? '') !== '') {
            $authorization = $args[self::AUTHORIZATION];
        } else {
            $activeProfile = $cache->getActiveProfile(true);
            $authorization = $cache->getXQAccess($activeProfile, true);
        }

        $encodedToken = rawurlencode($args[self::TOKEN]);

        $response = $this->sdk()->call(
            Config::ValidationHost(),
            implode("/" , array($this->name() , $encodedToken)),
            [],
            '',
            CallMethod::Get,
            Config::ValidationKey(), $authorization, $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

        if ($response->succeeded()) {
            $remaining = (int) $response->json()->expiresOn;
            $expirationDate = date('Y-m-d H:i:s', (time() + $remaining) );
            return new ServerResponse($response->responseCode() , [
                'remaining' => $remaining,
                'date' => $expirationDate
            ]);
        }

        return $response;
    }
}