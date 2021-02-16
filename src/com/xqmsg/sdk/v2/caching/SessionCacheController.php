<?php namespace com\xqmsg\sdk\v2\caching;

use com\xqmsg\sdk\v2\util\StatusCodes;
use com\xqmsg\sdk\v2\exceptions\StatusCodeException;


class SessionCacheController implements CacheController {

    private const DASHBOARD_PREFIX = 'das';
    private const XQ_PREFIX = 'xq';
    private const EXCHANGE_PREFIX = 'exchange';
    private const ACTIVE_PROFILE_KEY = 'active_profile';
    private const PROFILE_LIST_KEY = 'available_profiles';

    /**
     * SessionCacheController constructor.
     * @throws StatusCodeException
     */
    public function __construct()
    {
        if ( session_status() === PHP_SESSION_DISABLED ) {
            throw new StatusCodeException(
                "Sessions must be enabled in order to use this cache controller.",
                500);
        }
    }

    /**
     * @inheritDoc
     */
    public function addDashboardPreauth(string $user, string $token) : CacheController {
        $_SESSION[self::EXCHANGE_PREFIX.self::DASHBOARD_PREFIX.$user] = $token;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearDashboardPreauth(string $user) : void {
        unset($_SESSION[self::EXCHANGE_PREFIX.self::DASHBOARD_PREFIX.$user]);
    }

    /**
     * @inheritDoc
     */
    public function addXQPreauth(string $user, string $token) : CacheController {
        $_SESSION[self::EXCHANGE_PREFIX.self::XQ_PREFIX.$user] = $token;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearXQPreauth(string $user) : void {
        unset($_SESSION[self::EXCHANGE_PREFIX.self::XQ_PREFIX.$user]);
    }

    /**
     * @inheritDoc
     */
    public function addDashboardAccess(string $user, string $token) : CacheController {
        $_SESSION[self::DASHBOARD_PREFIX.$user] = $token;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearDashboardAccess(string $user) : void {
        unset($_SESSION[self::DASHBOARD_PREFIX.$user]);
    }

    /**
     * @inheritDoc
     */
    public function addXQAccess(string $user, string $token) : CacheController {
        $_SESSION[self::XQ_PREFIX.$user] = $token;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function clearXQAccess(string $user) : void {
        unset($_SESSION[self::XQ_PREFIX.$user]);
    }

    /**
     * @inheritDoc
     */
    public function getDashboardPreauth(string $user) : ?string {
        return $_SESSION[self::EXCHANGE_PREFIX.self::DASHBOARD_PREFIX.$user] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getXQPreauth(string $user) : ?string {
        return $_SESSION[self::EXCHANGE_PREFIX.self::XQ_PREFIX.$user] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getDashboardAccess(string $user, bool $required = false ) : ?string {
        $res = $_SESSION[self::DASHBOARD_PREFIX.$user] ?? null;
        if ($required && !$res ) {
            throw new StatusCodeException(
                "No access token available for selected profile.",
                StatusCodes::HTTP_UNAUTHORIZED
            );
        }
        return (!$res ) ? null : $res;
    }

    /**
     * @inheritDoc
     */
    public function getXQAccess(string $user, bool $required = false ) : ?string {
        $res = $_SESSION[self::XQ_PREFIX.$user] ?? null;
        if ($required && !$res ) {
            throw new StatusCodeException(
                "No access token available for selected profile.",
                StatusCodes::HTTP_UNAUTHORIZED
            );
        }
        return (!$res ) ? null : $res;
    }

    /**
     * @inheritDoc
     */
    public function hasProfile(string $user) : bool {
        if (!isset($_SESSION[self::PROFILE_LIST_KEY ])) {
            return false;
        }
        $list = $_SESSION[self::PROFILE_LIST_KEY];
        return in_array($user, $list, true);
    }

    /**
     * @inheritDoc
     */
    public function setActiveProfile(string $user) : void  {
        if (!isset($_SESSION[self::PROFILE_LIST_KEY ])) {
            $_SESSION[self::PROFILE_LIST_KEY] = array($user);
        }
        else {
            $list = $_SESSION[self::PROFILE_LIST_KEY];
            $list[] = $user;
            $_SESSION[self::PROFILE_LIST_KEY] = $list;
        }

        $_SESSION[self::ACTIVE_PROFILE_KEY] = $user;
    }

    /**
     * @inheritDoc
     */
    public function getActiveProfile(bool $required = false ) : ?string {
        $res = $_SESSION[self::ACTIVE_PROFILE_KEY ] ?? null;
        if ($required && !$res ) {
            throw new StatusCodeException(
                "You do not have an active profile set.",
                StatusCodes::HTTP_UNAUTHORIZED
            );
        }
        return (!$res ) ? null : $res;
    }

    /**
     * @inheritDoc
     */
    public function clearAllProfiles(): void
    {
        if (!isset($_SESSION)) {
            return;
        }
        if ( $list = ($_SESSION[self::PROFILE_LIST_KEY] ?? null) ) {
            foreach ($list as $user) {
                unset($_SESSION[self::XQ_PREFIX . $user],
                    $_SESSION[self::DASHBOARD_PREFIX . $user],
                    $_SESSION[self::EXCHANGE_PREFIX . self::DASHBOARD_PREFIX . $user],
                    $_SESSION[self::EXCHANGE_PREFIX . self::XQ_PREFIX . $user]);
            }
        }
        unset($_SESSION[self::ACTIVE_PROFILE_KEY], $_SESSION[self::PROFILE_LIST_KEY]);
    }

    /**
     * @inheritDoc
     */
    public function listProfiles(): array {
        if ( $list =( $_SESSION[self::PROFILE_LIST_KEY] ?? null )) {
            return $list;
        }
        return [];
    }

    /**
     * @inheritDoc
     */
    public function clearProfile(string $user) : void {

        if (!isset($_SESSION)) {
            return;
        }
        unset($_SESSION[self::XQ_PREFIX . $user],
            $_SESSION[self::DASHBOARD_PREFIX . $user],
            $_SESSION[self::EXCHANGE_PREFIX . self::DASHBOARD_PREFIX . $user],
            $_SESSION[self::EXCHANGE_PREFIX . self::XQ_PREFIX . $user]);

        if ( $list = ($_SESSION[self::PROFILE_LIST_KEY] ?? null ) ) {
            $list = array_diff($list, [$user]);
            $_SESSION[self::PROFILE_LIST_KEY] = $list;
            if (!empty($list)) {
                $this->setActiveProfile(current($list));
            }
        }
    }
}