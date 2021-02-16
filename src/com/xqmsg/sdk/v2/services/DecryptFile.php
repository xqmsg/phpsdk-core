<?php namespace com\xqmsg\sdk\v2\services;

use com\xqmsg\sdk\v2\algorithms\AESAlgorithm;
use com\xqmsg\sdk\v2\algorithms\OTPv2Algorithm;
use Config;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;
use com\xqmsg\sdk\v2\models\File;
use com\xqmsg\sdk\v2\ServerResponse;
use com\xqmsg\sdk\v2\util\StatusCodes;
use com\xqmsg\sdk\v2\XQModule;
use com\xqmsg\sdk\v2\XQSDK;
use JsonException;

/**
 * Class ValidateAccessRequest
 * @package com\xqmsg\sdk\v2\services
 *
 */
class DecryptFile extends XQModule
{
    public const  SOURCE = "source";

    public const TARGET = "target";

    public const ALGORITHM = "algorithm";

    public const REQUIRED = array(self::SOURCE, self::TARGET  );

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
        return "decryptfile";
    }

    /**
     * @param File $source
     * @param string $target
     * @param string $authorization
     * @return ServerResponse
     * @throws StatusCodeException | JsonException
     */
    public function runWith(File $source, string $target, string $authorization = ''): ServerResponse
    {
        return $this->run([
            self::SOURCE => $source,
            self::TARGET => $target,
            AddKey::AUTHORIZATION => $authorization,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function run(array $args): ServerResponse
    {

        $this->validateInput($args, self::REQUIRED);

        /** @var File */
        $source = $args[self::SOURCE];
        if (!file_exists($source->path) ) {
            throw new StatusCodeException("Source file was not found.");
        }

        $input = fopen($source->path, 'rb');
        $remainingBytes = filesize($source->path);
        if (!$input || $remainingBytes === 0 ) {
            throw new StatusCodeException("Source file is empty or could not be opened" );
        }

        /** @var false|resource $handle */
        $handle = false;
        $target = $args[self::TARGET] ?? '';

        // Local binary content will be stored here.
        $bin_content = '';

        // Read the first 4 bytes to get the token size.
        $tokenSize = (int) unpack('L', fread($input,4))[1];

        // Read the token.
        $messageToken = fread($input, $tokenSize);

        // Read the next 4 bytes to get the filename size.
        $filenameSize = (int)unpack('L', fread($input, 4))[1];

        // Read the actual encrypted file name.
        $filename = array_values(unpack('C*',  fread($input, $filenameSize) ) );

        // Use the extracted token to get the key from the server.
        $response = RetrieveKey::with($this->sdk())->runWith($messageToken, $args[RetrieveKey::AUTHORIZATION] ?? '' );
        if (!$response->succeeded()) {
            fclose($handle);
        }

        $key = $response->raw();

        if (isset($args[self::ALGORITHM])) {
            $algorithm = $args[self::ALGORITHM];
        }
        else if ($key[0] === '.') {
            switch ($key[1]) {
                case AESAlgorithm::PREFIX :
                    $algorithm = new AESAlgorithm();
                    break;
                default:
                    $algorithm = new OTPv2Algorithm();
            }
            $key = substr($key, 2); // Remove the prefix.
        }
        else {
            $classname = Config::DEFAULT_ALGORITHM;
            $algorithm = new $classname();
        }

        $keyData = array_values( unpack('C*', $key ) );
        $keyIndex = 0;
        $keyLength = count($keyData);

        // Recover the filename using the key.
        for ( $x = 0; $x < $filenameSize; ++$x ) {
            $filename[$x] ^= $keyData[ $keyIndex++ ];
            if ($keyIndex >= $keyLength) {
                $keyIndex = 0;
            }
        }

        $recoveredFilename = pack('C*', ...$filename);

        if ( $target !== "" ){
            $handle = fopen($target, "wb");
            if (!$handle) {
                return ServerResponse::error("Could not open file at " . $target);
            }
        }

        // Decrypt the file contents.
        $decrypted = $algorithm->decryptFile($keyData, $input, $handle);
        fclose($input);

        if ($handle) {
            $size = ftell($handle);
            fclose($handle);
            return new ServerResponse(StatusCodes::HTTP_OK, [
                'name' => $recoveredFilename,
                'size' => $size,
                'path' => $target,
            ]);
        }

        $bin_content.=$decrypted;
        $size = strlen($bin_content);
        return new ServerResponse(StatusCodes::HTTP_OK, [
            'name' => $recoveredFilename,
            'size' => $size,
            'data' => $bin_content,
        ]);

    }
}