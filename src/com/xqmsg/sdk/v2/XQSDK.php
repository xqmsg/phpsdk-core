<?php /** @noinspection PhpUnused */

namespace com\xqmsg\sdk\v2;

use Config;
use com\xqmsg\sdk\v2\caching\CacheController;
use Exception;
use com\xqmsg\sdk\v2\enums\CallMethod;



/**
 * Class XQSDK
 * @package xq\sdk
 * This class is responsible for speaking directly to the XQ servers.
 */
class XQSDK {

    private const START_TAG = "[XQ MSG START]";
    private const END_TAG = "[XQ MSG END]";
    private const TOKEN_LENGTH = 43;
    private ?CacheController $cache = null;

    public static function withPreauth(string $user, string $token  ) : XQSDK {
        $sdk = new self();
        $sdk->getCache()->addXQPreauth($user, $token )->setActiveProfile($user);
        return $sdk;
    }

    public static function withAccess(string $user, string $token  ) : XQSDK {
        $sdk = new self();
        $sdk->getCache()->addXQAccess($user, $token)->setActiveProfile($user);
        return $sdk;
    }

    /**
     * Make a HTTP CURL request based on the provided parameters.
     * @param string $host
     * @param string $endpoint
     * @param array $params
     * @param string $body
     * @param string $method
     * @param string $apiKey
     * @param string $bearerAuth
     * @param string $language
     * @return ServerResponse
     */
    public function call( string $host,
                          string $endpoint,
                          array $params,
                          string $body,
                          string $method,
                          string $apiKey,
                          string $bearerAuth,
                          string $language
                            ) : ServerResponse {

        $ch = null;

        try {

            $url = $host . "/" . $endpoint;

            if (!empty($params) ) {
                $url .= '?' . http_build_query( $params );
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_URL, $url );
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            $headers = array();

            if ( $bearerAuth && $bearerAuth !== '' ) {
                $headers[] = "Authorization:Bearer " . $bearerAuth;
            }
            if ($apiKey && $apiKey !== '') {
                $headers[] = "Api-Key:" . $apiKey;
            }
            if ($language && $language !== '') {
                $headers[] = "Accept-Language:" . $language;
            }

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, Config::SERVER_TIMEOUT);
            curl_setopt($ch, CURLOPT_TIMEOUT, Config::SERVER_TIMEOUT);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers );

            if ( $method !== CallMethod::Get && $body && $body !== '' ) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body );
            }

            $output = curl_exec($ch);
            $responseCode = curl_getinfo ( $ch, CURLINFO_HTTP_CODE ) ;
            if ($output === false) {
                $output = curl_error($ch);
                $response = ServerResponse::error($output);
            }

            else {
                $response = new ServerResponse($responseCode, $output );
            }

            curl_close($ch);

            return $response;
        }

        catch (Exception $e) {

            if ( $ch !== null ) {
                curl_close($ch);
            }

            return ServerResponse::error($e->getMessage());
        }
    }

    /**
     * @return CacheController
     */
    public function getCache() : CacheController {
        if (!$this->cache) {
            $classname = Config::CACHE_CLASS;
            $this->cache = new $classname();
        }
        return $this->cache;
    }

    /**
     * @param $profile
     * @return bool
     */
    public function switchProfile($profile) : bool {
        if (!$this->cache || !$this->getCache()->hasProfile($profile) ) {
            return false;
        }
        $this->getCache()->setActiveProfile($profile);
        return true;
    }

    /**
     * @param string $body
     * @param string $token
     * @param ?array $to
     * @return string
     */
    public function encodeLink( string $body, string $token, ?array $to = [] ) : string {
        $body = base64_encode( self::START_TAG. $token . $body . self::END_TAG );
        $org = implode(unpack("H*", Config::Organization()));
        $link = Config::MESSAGE_HOST_PREFIX . "?b=" . $body . "&o=" . $org;
        return (empty($to)) ? $link : $link . '&to=' . base64_encode(implode(',', $to));
    }

    /**
     * @param string $link
     * @return ?object
     */
    public function decodeLink(string $link) : ?object {

        parse_str(parse_url($link,PHP_URL_QUERY),$op);

        if (!isset($op['b'])) {
            return null;
        }

        $organization =  $op['o'] ?? '';
        $body = base64_decode($op['b']);
        $startTagLength = strlen(self::START_TAG) ;
        $bodyLength = strlen($body)  - (self::TOKEN_LENGTH + $startTagLength + strlen((self::END_TAG)));
        $token = substr( $body,$startTagLength, self::TOKEN_LENGTH );
        $data =  substr( $body,$startTagLength + self::TOKEN_LENGTH, $bodyLength );
        $recipients = isset($op['to']) ? explode(',', base64_decode($op['to'])) : [];

        return (object)[
            'data' => $data,
            'token' => $token,
            'organization' => $organization,
            'to' => $recipients
        ];
    }
}