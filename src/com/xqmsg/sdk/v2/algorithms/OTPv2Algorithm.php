<?php namespace com\xqmsg\sdk\v2\algorithms;

use Config;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;

/**
 * Class OTPAlgorithm
 * @package App\Application\Algorithms
 */
class OTPv2Algorithm implements Algorithm
{

    public const PREFIX = 'B';

    /**
     * Get the key prefix used to identify the algorithm type.
     * @return string
     */
    public function prefix() : string {
        return self::PREFIX;
    }

    /**
     * @inheritDoc
     */
    public function encrypt(string $key, string $data) : string
    {
        $keyBytes = unpack('C*', $key);
        $encoded = rawurlencode ( $data ) ;
        $payloadBytes = unpack('C*', $encoded );

        $encoded = array();

        foreach ( $keyBytes as $k => $v ) {
            if ( array_key_exists($k, $payloadBytes) ) {
                $encoded[] = $v ^ $payloadBytes[$k];
            }
        }

        $data = pack('C*', ...$encoded);
        return base64_encode($data);
    }

    /**
     * @inheritDoc
     */
    public function decrypt(string $key, string $data) : string
    {
        $keyBytes = unpack('C*', $key);
        $payload = base64_decode( $data );
        $decoded = array();

        $payloadBytes = unpack('C*', $payload  );

        foreach ( $keyBytes as $k => $v ) {
            if ( array_key_exists($k, $payloadBytes) ) {
                $decoded[] = $v ^ $payloadBytes[$k];
            }
        }

        $url_encoded  = pack('C*', ...$decoded);
        return rawurldecode($url_encoded);
    }


    /**
     * @inheritDoc
     */
    public function encryptFile( array $keyData, string $source_url, $output_handle ): string
    {
        $bin_content = '';
        $keyLength = count($keyData);

        $sink = static function($content) use ($output_handle, &$bin_content) {
            if ($output_handle) {
                fwrite($output_handle, $content);
            }
            else {
                $bin_content .= $content;
            }
        };


        $source_handle = fopen($source_url, 'rb');
        if (!$source_handle) {
            throw new StatusCodeException("Failed to open file source.");
        }

        $keyIndex = 0;
        $streamBufferSize = 0;
        $streamBuffer = array_fill(0, Config::STREAM_CHUNK_SIZE, 0);

        do {

            if (!($bytesRead = stream_get_contents($source_handle, Config::STREAM_CHUNK_SIZE))) {
                break;
            }

            $contentBytes = array_values(unpack('C*', $bytesRead));

            foreach ($contentBytes as $xValue) {
                $streamBuffer[$streamBufferSize++] =  $xValue ^  $keyData[ $keyIndex++ ];
                if ($streamBufferSize >= Config::STREAM_CHUNK_SIZE ) {
                    $sink(pack('C*', ...$streamBuffer));
                    $streamBufferSize = 0;
                }
                if ($keyIndex >= $keyLength) {
                    $keyIndex = 0;
                }
            }

        } while ($bytesRead);

        if ($streamBufferSize > 0) {
            $written = array_slice( $streamBuffer, 0, $streamBufferSize);
            $sink(pack('C*', ...$written));
        }

        return $bin_content;


    }

    /**
     * @inheritDoc
     */
    public function decryptFile(array $keyData, $source_handle, $output_handle): string
    {
        $bin_content = '';
        $keyLength = count($keyData);

        $sink = static function($content) use ($output_handle, &$bin_content) {
            if ($output_handle) {
                fwrite($output_handle, $content);
            }
            else {
                $bin_content .= $content;
            }
        };

        $streamBufferSize = 0;
        $streamBuffer = array_fill(0, Config::STREAM_CHUNK_SIZE, 0);

        $keyIndex = 0;
        do {

            if (!($byteRead = stream_get_contents($source_handle, Config::STREAM_CHUNK_SIZE))){
                break;
            }
            $contentBytes = array_values(unpack('C*', $byteRead ));

            foreach ($contentBytes as $xValue) {
                $streamBuffer[$streamBufferSize++] =  $xValue ^  $keyData[ $keyIndex++ ];
                if ($streamBufferSize >= Config::STREAM_CHUNK_SIZE ) {
                    $sink(pack('C*', ...$streamBuffer));
                    $streamBufferSize = 0;
                }
                if ($keyIndex >= $keyLength) {
                    $keyIndex = 0;
                }
            }

        } while ($byteRead);

        if ($streamBufferSize > 0) {
            $written = array_slice( $streamBuffer, 0, $streamBufferSize);
            $sink(pack('C*', ...$written));
        }
        return $bin_content;
    }
}