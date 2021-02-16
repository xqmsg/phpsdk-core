<?php namespace com\xqmsg\sdk\v2;

use com\xqmsg\sdk\v2\util\StatusCodes;

/**
 * Class ServerResponse
 * This class encapsulates all responses received from the XQ servers.
 */
class ServerResponse {

    private int $responseCode;

    /**
     * @var string|array|object
     */
    private $payload;

    /**
     * ServerResponse constructor.
     * @param int $responseCode
     * @param string|array|object $payload
     */
    public function __construct(int $responseCode, $payload )
    {
        $this->responseCode = $responseCode;
        $this->payload = $payload;
    }

    /**
     * @return bool
     */
    public function succeeded() : bool {
        return $this->responseCode !== 0 && $this->responseCode < 400;
    }

    /**
     * @return int
     */
    public function responseCode() : int {
        return $this->responseCode;
    }

    /**
     * @return object
     */
    public function json() : object {
        if (is_string($this->payload)) {
            return json_decode($this->payload, false);
        } else {
            return (object) $this->payload;
        }
    }

    /**
     * @return string
     */
    public function raw() : string {
        if (is_string($this->payload)) {
            return $this->payload;
        } else {
            return json_encode($this->payload, false);
        }
    }

    /**
     * @return string
     */
    public function status() : string {

        if ($this->succeeded()) return "OK";

        if (is_string($this->payload)) {
            $result = json_decode($this->payload, false);
            if (!$result || !isset($result->status)){
                return StatusCodes::getMessageForCode( $this->responseCode()) . " - " . $this->payload;
            }
            return StatusCodes::getMessageForCode( $this->responseCode()) . " - " . $result->status;
        }
        else {
            $result = $this->payload;
            if (!$result || !isset($result->status)){
                return StatusCodes::getMessageForCode( $this->responseCode());
            }
            return StatusCodes::getMessageForCode( $this->responseCode()) . " - " . $result->status;
        }


    }

    /**
     * @param string $reason
     * @return ServerResponse
     */
    public static function error(string $reason) : ServerResponse {
        return new ServerResponse(0, ['status' => $reason ]);
    }

    /**
     * @param string|array|object $result
     * @return ServerResponse
     */
    public static function ok( $result ) : ServerResponse {
        return new ServerResponse(StatusCodes::HTTP_OK, $result );
    }
}