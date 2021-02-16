<?php namespace com\xqmsg\sdk\v2\services;

use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use Config;
use com\xqmsg\sdk\v2\enums\CallMethod;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;
use Exception;
use JsonException;

class GetQuantumEntropy extends XQModule {

    public const KEY_SIZE = 'ks';
    public const RAW = 'raw';

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
        return "subscriber";
    }

    /**
     * @param int $size
     * @param bool $raw
     * @return ServerResponse
     * @throws StatusCodeException | JsonException
     */
    public function runWith(int $size, bool $raw) : ServerResponse {
        return $this->run([self::KEY_SIZE => $size, self::RAW => $raw]);
    }

    /**
     * @inheritDoc
     */
    public function run( array $args = [self::KEY_SIZE => Config::DEFAULT_KEY_SIZE  ] ): ServerResponse
    {
        $keySize = $args[self::KEY_SIZE] ?? Config::DEFAULT_KEY_SIZE;
        if (isset($args[self::RAW]) && is_bool($args[self::RAW] )){
            $raw = $args[self::RAW] ? 1 : 0;
        }
        else {
            $raw = 0;
        }

        return $this->sdk()->call(
            Config::QuantumHost(),
            '', ['ks' => $keySize, 'raw' => $raw ],
            '',
            CallMethod::Get,
            '',
            '',
            '');

    }

    /**
     * Randomly expand the string into the desired length.
     * @param string $string
     * @param int $length The desired length.
     * @return string The expanded key.
     * @throws StatusCodeException
     */
    public static function extend( string $string, int $length ) : string
    {
        // Enforce a minimum entropy extension length of 2048

        $length = min($length, 2048);

        $keyLength = strlen($string);

        try {

            if ($keyLength >= $length) {
                return str_shuffle($string);
            }

            while ($keyLength < $length) {
                $string .= str_shuffle($string);
                $keyLength = strlen($string);
            }
        }
        catch (Exception $e) {
            /** @noinspection JsonEncodingApiUsageInspection */
            throw new StatusCodeException(0, json_encode(['status' => $e->getMessage()]) );
        }

        return str_shuffle(substr( $string, 0, $length ) );
    }
}