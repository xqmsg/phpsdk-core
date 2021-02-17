<?php

use com\xqmsg\sdk\v2\algorithms\OTPv2Algorithm;
//use com\xqmsg\sdk\v2\caching\MemcachedController;
use com\xqmsg\sdk\v2\caching\SessionCacheController;

/**
 * Class Config
 * Configuration constants should be stored in this file.
 */
class Config {
    /**
     * The API key for interacting with the subscription backend.
     */
    private const API_KEY = "YOUR_API_KEY";
    /**
     * The main XQ subscription server that this implementation will be comumunicating with. Will remain the same in
     * most cases, unless the user has an enterprise XQ installation.
     */
    public const URL_SUBSCRIPTION = "https://subscription.xqmsg.net/v2";
    /**
     * The main validation server that this implementation will be communicating with. Will remain the same in
     * most cases, unless the user has an enterprise XQ installation.
     */
    public const URL_VALIDATION = "https://validation.xqmsg.net/v2";
    /**
     * The main quantum server that this implementation will be communicating with. Will remain the same in
     * most cases, unless the user has an enterprise XQ installation.
     */
    public const URL_QUANTUM = "https://quantum.xqmsg.net/v2";

    /**
     * The prefix that will be attached to encrypted text links. In most cases this will not change, unless the user
     * has a custom front-end for decrypting messages.
     */
    public const MESSAGE_HOST_PREFIX      = "https://xqmsg.net/applink";

    public const ORGANIZATION_TAG = "xq";

    public const DEFAULT_ALGORITHM = OTPv2Algorithm::class;

    /**
     * The default size of the encryption key.
     */
    public const DEFAULT_KEY_SIZE         = 2048;

    public const DEFAULT_LANGUAGE = "en_US";

    public const SERVER_TIMEOUT = 15;

    public const CACHE_SERVER_URL = 'localhost';

    public const CACHE_SERVER_PORT = 11211;

    public const STREAM_CHUNK_SIZE = 1024;

    public const CACHE_CLASS = SessionCacheController::class;


    public static function ApiKey() : string {
        return ($value = getenv("API_KEY")) ? $value : self::API_KEY ;
    }

    public static function SubscriptionHost() : string {
        return ($value = getenv("URL_SUBSCRIPTION")) ? $value : self::URL_SUBSCRIPTION ;
    }

    public static function ValidationHost() : string {
        return ($value = getenv("URL_VALIDATION")) ? $value : self::URL_VALIDATION ;
    }

    public static function QuantumHost() : string {
        return ($value = getenv("URL_QUANTUM")) ? $value : self::URL_QUANTUM ;
    }

    public static function Organization() : string {
        return ($value = getenv("ORGANIZATION_TAG")) ? $value : self::ORGANIZATION_TAG ;
    }

}