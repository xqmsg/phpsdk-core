<?php namespace com\xqmsg\sdk\v2\services;

use com\xqmsg\sdk\v2\algorithms\Algorithm;
use Config;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use com\xqmsg\sdk\v2\models\File;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;
use JsonException;

/**
 * Class ValidateAccessRequest
 * @package com\xqmsg\sdk\v2\services
 *
 */
class EncryptFile extends XQModule
{
    public const  SOURCE = "source";
    public const TARGET = "target";
    public const  DATA = "data";
    public const ALGORITHM = "algorithm";
    public const REQUIRED = array(self::SOURCE, self::TARGET, self::ALGORITHM, AddKey::RECIPIENTS, AddKey::EXPIRES_HOURS );

    /**
     * @param XQSDK $sdk
     * @return static
     */
    public static function with(XQSDK $sdk): self
    {
        return new self($sdk);
    }

    /**
     * @inheritDoc
     */
    public function name(): string
    {
        return "encryptfile";
    }

    /**
     * @param File $source
     * @param string $target
     * @param string $recipients
     * @param Algorithm|null $algorithm
     * @param int $expiresIn
     * @param string $authorization
     * @return ServerResponse
     * @throws StatusCodeException | JsonException
     */
    public function runWith(File $source, string $target, string $recipients, ?Algorithm $algorithm = null, int $expiresIn = 24, string $authorization = ''): ServerResponse
    {
        return $this->run([
            self::SOURCE => $source,
            self::TARGET => $target,
            self::ALGORITHM => $algorithm,
            AddKey::RECIPIENTS => $recipients,
            AddKey::EXPIRES_HOURS => $expiresIn,
            AddKey::AUTHORIZATION => $authorization,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function run(array $args): ServerResponse
    {

        $this->validateInput($args, self::REQUIRED);

        // Confirm that the file exists.
        /** @var File */
        $source = $args[self::SOURCE];
        if (!file_exists($source->path)) {
            throw new StatusCodeException("Source file was not found." );
        }

        /** @var false|resource $handle */
        $handle = false;
        $target = $args[self::TARGET] ?? '';
        if ($target !== '') {
            $handle = fopen($target, 'wb');
            if (!$handle) {
                throw new StatusCodeException("Target cannot be written: " . $target );
            }
        }

        if (!isset($args[self::ALGORITHM])) {
            $classname = Config::DEFAULT_ALGORITHM;
            $algorithm = new $classname();
        }
        else if (is_a($args[self::ALGORITHM], Algorithm::class ) ) {
            $algorithm = $args[self::ALGORITHM];

        }

        else {
            throw new StatusCodeException("Invalid algorithm provided" );
        }

        //Get a new quantum key.
        $response = GetQuantumEntropy::with($this->sdk())->run();
        if (!$response->succeeded()) {
            return $response;
        }

        $secret = GetQuantumEntropy::extend( $response->raw() , 2048 );

        $prefixedSecret = ($algorithm->prefix() === '') ? $secret : '.'.$algorithm->prefix() .$secret;

        $response = AddKey::with($this->sdk())->runWith(
            $prefixedSecret,
            $args[AddKey::RECIPIENTS],
            $args[AddKey::EXPIRES_HOURS],
            AddKey::TYPE_FILE, [
                'title' => $source->name,
                'type' => $source->type,
                'size' => $source->size  ],
            $args[AddKey::AUTHORIZATION] ?? '' );

        if (!$response->succeeded()) {
            return ServerResponse::error( $response->status() );
        }

        $token = $response->raw();

        $keyData = array_values( unpack('C*', $secret ) );
        $keyIndex = 0;
        $keyLength = count($keyData);

        $target = $args[self::TARGET];

        // Local binary content will be stored here.
        $bin_content = '';

        // The closure appends data based on whether a file handle was supplied or not.
        $sink = static function($content) use ($handle, &$bin_content) {
            if ($handle) {
                fwrite($handle, $content);
            }
            else {
                $bin_content .= $content;
            }
        };

        // Add token information;
        $sink(pack('L', strlen($token)));
        $sink($token);

        // Encode the file name
        $filename = array_values(unpack("C*", $source->name));
        $nameLength = count($filename);

        foreach ($filename as $x => $xValue) {
            $filename[$x] ^= $keyData[$keyIndex++];
            if ($keyIndex >= $keyLength) {
                $keyIndex = 0;
            }
        }

        /// Write filename information to output
        $sink( pack('L', $nameLength )); // Add Filename Size
        $sink( pack('C*', ...$filename )); // Add encrypted filename

        /// Encode the file bytes
        $encrypted = $algorithm->encryptFile( $keyData, $source->path, $handle );

        if ($handle) {
            fclose($handle);
            return ServerResponse::ok( $target );
        }

        $bin_content.=$encrypted;
        return ServerResponse::ok( $bin_content );

    }
}