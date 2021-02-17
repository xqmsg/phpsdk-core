<?php

namespace com\xqmsg\sdk\v2\services;

use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;
use JsonException;

/**
 * Class ValidateAccessRequest
 * @package com\xqmsg\sdk\v2\services
 *
 */
class AddKey extends XQModule
{
    public const  KEY = "key";
    public const AUTHORIZATION = 'authorization';
    public const  RECIPIENTS = "recipients";
    public const  EXPIRES_HOURS = "expires";
    public const  META = "meta";
    public const  DELETE_ON_RECEIPT = "onetime";
    public const PIN = 'pin';
    public const TYPE = "type";

    public const TYPE_FILE = "file";
    public const TYPE_EMAIL = "email";
    public const TYPE_OTHER = "other";

    public const REQUIRED = array(self::KEY, self::RECIPIENTS, self::EXPIRES_HOURS, self::TYPE);

    /**
     * @param XQSDK $sdk
     * @return static
     */
    public static function with(XQSDK $sdk): self
    {
        return new self($sdk);
    }

    public function name(): string
    {
        return "packet";
    }

    /**
     * @param string $secret
     * @param string $recipients
     * @param int $expiresIn
     * @param string $type
     * @param array $meta
     * @param string $authorization
     * @return ServerResponse
     * @throws JsonException
     * @throws StatusCodeException
     */
    public function runWith(string $secret, string $recipients, int $expiresIn = 24, string $type = AddKey::TYPE_EMAIL, array $meta = [], string $authorization = ''): ServerResponse
    {
        return $this->run([
            self::KEY => $secret,
            self::RECIPIENTS => $recipients,
            self::EXPIRES_HOURS => $expiresIn,
            self::TYPE => $type,
            self::META => $meta,
            self::AUTHORIZATION => $authorization
        ]);
    }

    /**
     * @inheritDoc
     */
    public function run(array $args): ServerResponse
    {

        $this->validateInput($args, self::REQUIRED);
        $cache = $this->sdk()->getCache();

        // Ensure that there is an active profile and access token.
        if (($args[self::AUTHORIZATION] ?? '') !== '') {
            $authorization = $args[self::AUTHORIZATION];
        } else {
            $activeProfile = $cache->getActiveProfile(true);
            $authorization = $cache->getXQAccess($activeProfile, true);
        }

        $generated = $this->sdk()->call(
            Config::SubscriptionHost(),
            $this->name(), [],
            json_encode($args, JSON_THROW_ON_ERROR),
            CallMethod::Post,
            Config::ApiKey(), $authorization, $args['_lang'] ?? Config::DEFAULT_LANGUAGE
        );

        if ($generated->succeeded()) {
            return $this->sdk()->call(
                Config::ValidationHost(),
                $this->name(), [],
                $generated->raw(),
                CallMethod::Post,
                Config::ApiKey(), $authorization, $args['_lang'] ?? Config::DEFAULT_LANGUAGE
            );
        }

        return $generated;

    }
}